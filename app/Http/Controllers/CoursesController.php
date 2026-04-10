<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TypeCourse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
        $courseName = $request->query('course_name', '');
        $isActive   = $request->query('is_active');

        $query = Course::with('typeCourse');

        if ($courseName !== '') {
            $query->where('name', 'like', "%$courseName%");
        }
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $courses     = $query->get()->toArray();
        $courseTypes = TypeCourse::all()->toArray();

        return view('adminDashboard.coursesManagement', compact('courses', 'courseTypes'));
    }

    public function toggleStatus(Request $request)
    {
        $id = $request->input('id');
        if (!$id) return redirect('/courses');

        $course = Course::find($id);
        if ($course) {
            $course->update(['is_active' => !$course->is_active]);
            (new LogsController())->logAction('edit-course');
            return redirect('/courses')->with('message', 'Status do curso atualizado com sucesso!');
        }

        return redirect('/courses')->with('error', 'Erro ao atualizar status do curso');
    }

    public function addCourse(Request $request)
    {
        $courseTypeId = (int) $request->input('course_type', 0);
        $courseName   = $request->input('course_name');
        $isActive     = $request->input('is_course_active') === '1';

        if (!$courseTypeId || !$courseName) {
            return redirect('/courses')->with('error', 'Preencha todos os campos obrigatórios!');
        }

        try {
            Course::create([
                'name'          => $courseName,
                'type_course_id' => $courseTypeId,
                'is_active'     => $isActive,
            ]);

            (new LogsController())->logAction('create-course');
            return redirect('/courses')->with('message', 'Curso adicionado com sucesso!');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect('/courses')->with('error', 'Já existe um curso com esse nome.');
            }
            return redirect('/courses')->with('error', 'Erro ao adicionar curso: ' . $e->getMessage());
        }
    }

    public function editCourseName(Request $request)
    {
        $id      = $request->input('id');
        $newName = $request->input('new_name');

        if (!$id) return redirect('/courses');

        $course = Course::find($id);
        if ($course) {
            $course->update(['name' => $newName]);
            (new LogsController())->logAction('edit-course');
            return redirect('/courses')->with('message', 'Nome do curso atualizado com sucesso!');
        }

        return redirect('/courses')->with('error', 'Erro ao atualizar nome do curso');
    }

    public function deleteCourse(Request $request)
    {
        $id = (int) $request->input('course_id', 0);
        if (!$id) return redirect('/courses');

        try {
            Course::destroy($id);
            (new LogsController())->logAction('delete-course');
            return redirect('/courses')->with('message', 'Curso eliminado com sucesso!');
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), '1451')) {
                return redirect('/courses')->with('error', 'Erro ao eliminar curso: O curso está em uso por algum utilizador.');
            }
            return redirect('/courses')->with('error', 'Erro ao eliminar curso: ' . $e->getMessage());
        }
    }
}
