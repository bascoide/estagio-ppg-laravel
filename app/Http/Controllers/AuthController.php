<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
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
        $email    = $request->input('email', '');
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            return back()->with('error', 'Email e senha são obrigatórios!');
        }

        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return back()->with('error', 'Email ou senha inválidos!');
            }

            if (!$user->verified) {
                return back()->with('error', 'Verifique a sua conta primeiro! Use o link enviado no seu email.');
            }

            if (!Hash::check($password, $user->password)) {
                return back()->with('error', 'Email ou senha inválidos!');
            }

            session(['user_id' => $user->id, 'admin' => (bool) $user->admin]);

            if ($user->admin) {
                return redirect('/set-name');
            }

            return redirect('/guia-form');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao fazer login: ' . $e->getMessage());
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
        $name     = $request->input('name', '');
        $email    = $request->input('email', '');
        $password = $request->input('password', '');

        if (empty($name) || empty($email) || empty($password)) {
            return back()->with('error', 'Email e senha são obrigatórios!');
        }

        if (!preg_match('/@iscap\.ipp\.pt$/', $email)) {
            return back()->with('error', 'Apenas emails com domínio @iscap.ipp.pt são permitidos!');
        }

        $hashedPassword = Hash::make($password);

        try {
            $existingUser = User::where('email', $email)->first();

            if ($existingUser && $existingUser->verified) {
                return back()->with('error', 'Email já está registado!');
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
                'course_id'         => (int) $request->input('Course'),
                'verification_code' => $verificationCode,
                'verified'          => false,
            ]);

            if (!$user) {
                throw new Exception('Falha ao criar utilizador');
            }

            (new EmailService())->sendConfirmationCode($email, $verificationCode);

            return redirect('/login')->with('message', 'Utilizador criado com sucesso! Verifique o seu email para acabar a verificação.');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao criar utilizador: ' . $e->getMessage());
        }
    }

    public function verifyUser(Request $request)
    {
        $email            = $request->query('email', '');
        $verificationCode = $request->query('verification_code', '');

        if (empty($email) || empty($verificationCode)) {
            return view('verifyUser')->with('error', 'Link inválido');
        }

        $updated = User::where('email', $email)
            ->where('verification_code', $verificationCode)
            ->update(['verified' => 1]);

        if ($updated) {
            return view('verifyUser')->with('message', 'Conta verificada com sucesso!');
        }

        return view('verifyUser')->with('error', 'Erro ao verificar conta :(');
    }

    public function logout()
    {
        session()->flush();
        return redirect('/login');
    }

    public function showSetName()
    {
        return view('adminDashboard.setName');
    }

    public function setAdminName(Request $request)
    {
        if ($request->has('admin_name')) {
            session(['admin_name' => $request->input('admin_name')]);
            return redirect('/view-pending-documents');
        }

        return view('adminDashboard.setName');
    }
}
