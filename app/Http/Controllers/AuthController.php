<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\EmailService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $email    = strtolower(trim((string) $request->input('email', '')));
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            return back()->with('error', 'E-mail e palavra-passe são obrigatórios!');
        }

        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return back()->with('error', 'E-mail ou palavra-passe inválidos!');
            }

            if (!$user->verified) {
                return back()->with('error', 'Verifique primeiro a sua conta. Use o link enviado para o seu e-mail.');
            }

            if (!Hash::check($password, $user->password)) {
                return back()->with('error', 'E-mail ou palavra-passe inválidos!');
            }

            $request->session()->regenerate();
            session(['user_id' => $user->id, 'admin' => (bool) $user->admin]);

            if ($user->admin) {
                return redirect('/set-name');
            }

            return redirect('/guia-form');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao iniciar sessão: ' . $e->getMessage());
        }
    }

    public function showRegister()
    {
        $courses      = Course::with('typeCourse')->get()->toArray();
        $coursesTypes = \App\Models\TypeCourse::all()->toArray();
        return view('register', compact('courses', 'coursesTypes'));
    }

    public function register(Request $request)
    {
        $name         = trim((string) $request->input('name', ''));
        $email        = strtolower(trim((string) $request->input('email', '')));
        $password     = $request->input('password', '');
        $courseTypeId = (int) $request->input('CourseType', 0);
        $courseId     = (int) $request->input('Course', 0);

        if (empty($name) || empty($email) || empty($password)) {
            return back()->with('error', 'Nome, e-mail e palavra-passe são obrigatórios!');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'E-mail inválido!');
        }

        if (strlen((string) $password) < 8) {
            return back()->with('error', 'A palavra-passe deve ter pelo menos 8 caracteres!');
        }

        if (!preg_match('/@iscap\.ipp\.pt$/i', $email)) {
            return back()->with('error', 'Apenas e-mails com domínio @iscap.ipp.pt são permitidos!');
        }

        $course = Course::where('id', $courseId)
            ->where('type_course_id', $courseTypeId)
            ->where('is_active', true)
            ->first();

        if (!$course) {
            return back()->with('error', 'Curso inválido ou inativo para o tipo de curso selecionado!');
        }

        $hashedPassword = Hash::make($password);

        try {
            $existingUser = User::where('email', $email)->first();

            if ($existingUser && $existingUser->verified) {
                return back()->with('error', 'E-mail já registado!');
            }

            if ($existingUser && !$existingUser->verified) {
                $existingUser->delete();
            }

            $verificationCode = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(24))), 0, 32);

            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'password'          => $hashedPassword,
                'admin'             => false,
                'course_id'         => $course->id,
                'verification_code' => $verificationCode,
                'verified'          => false,
            ]);

            if (!$user) {
                throw new Exception('Falha ao criar utilizador');
            }

            (new EmailService())->sendConfirmationCode($email, $verificationCode);

            return redirect('/login')->with('message', 'Utilizador criado com sucesso. Verifique o seu e-mail para concluir a verificação.');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao criar utilizador: ' . $e->getMessage());
        }
    }

    public function verifyUser(Request $request)
    {
        $email            = $request->query('email', '');
        $verificationCode = $request->query('verification_code', '');

        if (empty($email) || empty($verificationCode)) {
            return view('verifyUser')->with('error', 'Link inválido.');
        }

        $updated = User::where('email', $email)
            ->where('verification_code', $verificationCode)
            ->update(['verified' => 1]);

        if ($updated) {
            return view('verifyUser')->with('message', 'Conta verificada com sucesso!');
        }

        return view('verifyUser')->with('error', 'Erro ao verificar a conta.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showSetName()
    {
        return view('adminDashboard.setName');
    }

    public function setAdminName(Request $request)
    {
        if ($request->has('admin_name')) {
            $adminName = trim((string) $request->input('admin_name'));
            if ($adminName === '' || mb_strlen($adminName) > 100) {
                return back()->with('error', 'Nome inválido.');
            }

            session(['admin_name' => $adminName]);
            return redirect('/view-pending-documents');
        }

        return view('adminDashboard.setName');
    }
}
