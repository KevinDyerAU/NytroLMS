<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class QuestionController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Quiz $quiz)
    {
        $this->authorize('update lms');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'LMS'],
            ['link' => route('lms.quizzes.index'), 'name' => 'Quiz'],
            ['name' => 'Edit Quiz'],
        ];
        $actionItems = [
            0 => ['link' => route('lms.quizzes.edit', $quiz), 'icon' => 'file-text', 'title' => 'View Quiz'],
            1 => ['link' => route('lms.quizzes.create'), 'icon' => 'plus-square', 'title' => 'Add New Quiz'],
        ];
        $questions = $quiz->questions()->orderBy('order');

        return view()->make('content.lms.post.add-edit')
            ->with([
                'questions' => true,
                'action' => ['url' => route('lms.quizzes.update', $quiz), 'name' => 'Edit'],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'post' => ['title' => 'Quiz', 'parent' => 'lesson', 'type' => 'quiz', 'content' => $quiz],
                'related' => [
                    'type' => 'quizzes',
                    'lvl1' => $questions->get(),
                ],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('update lms');

        $validated = $request->validate(
            [
            'question.*.order' => 'required|numeric',
            'question.*.title' => 'required',
            'question.*.required' => 'nullable',
            'question.*.answer_type' => 'required|alpha',
            'question.*.content' => 'required',
            'question.*.table_structure' => [
                'nullable',
                'json',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $decoded = json_decode($value, true);
                        if (!isset($decoded['columns']) || !is_array($decoded['columns']) || empty($decoded['columns'])) {
                            $fail('Table question must have at least one column.');
                        }
                        if (!isset($decoded['rows']) || !is_array($decoded['rows']) || empty($decoded['rows'])) {
                            $fail('Table question must have at least one row.');
                        }
                        foreach ($decoded['columns'] as $column) {
                            if (!isset($column['heading']) || empty($column['heading'])) {
                                $fail('All columns must have a heading text.');
                            }
                        }
                        foreach ($decoded['rows'] as $row) {
                            if (!isset($row['heading']) || empty($row['heading'])) {
                                $fail('All rows must have a heading text.');
                            }
                        }
                    }
                },
            ],
            'question.*.correct_answer' => 'sometimes',
        ],
            [
                'question.*.title.required' => 'Question title is required',
                'question.*.content.required' => 'Question content is required',
                'question.*.correct_answer.required_if' => 'Correct Answer required',
            ]
        );

        if (!empty($request->question)) {
            foreach ($request->question as $q) {
                $data = [
                    'order' => intval($q['order']) ?? 0,
                    'required' => $q['required'] ?? 0,
                    'title' => $q['title'],
                    'content' => $q['content'],
                    'answer_type' => $q['answer_type'],
                    'options' => $this->getOptionCollection($q, \Str::lower($q['answer_type'])),
                    'correct_answer' => $this->correctAnswer($q),
                ];

                // Handle table question type
                if ($q['answer_type'] === 'TABLE' && isset($q['table_structure'])) {
                    $tableStructure = json_decode($q['table_structure'], true);
                    // Set the table_question_title, default to 'Question' if empty
                    $tableStructure['table_question_title'] = isset($q['table_question_title']) && trim($q['table_question_title']) !== '' ? $q['table_question_title'] : 'Question';
                    $data['table_structure'] = $tableStructure;
                }

                $question = Question::updateOrCreate(
                    ['slug' => $q['slug'], 'quiz_id' => $quiz->id],
                    $data
                );
            }
        }
        $this->updateQuestionsForAttempts($quiz);

        return redirect()->route('lms.quizzes.edit', $quiz)
            ->with('success', 'Quiz created successfully.');
    }

    public function updateQuestionsForAttempts($quiz)
    {
        $questions = $quiz->questions()->orderBy('order')->get()->toArray();
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)->get();
        foreach ($attempts as $attempt) {
            $attempt->questions = $questions;
            $attempt->save();
        }
    }

    public function destroy(Question $question)
    {
        $this->authorize('delete lms');

        // Soft delete the question instead of hard delete
        $question->softDelete();

        return response()->json([
            'data' => $question,
            'success' => true, 'status' => 'success',
            'message' => 'Question Deleted Successfully!',
        ], 202);
    }

    private function getOptionCollection(mixed $q, $key = 'scq'): array|Collection
    {
        return !empty($q['options']) ? (!empty($q['options'][$key]) ? [$key => array_filter($q['options'][$key])] : array_filter($q['options'])) : new Collection();
    }

    public function correctAnswer($q): mixed
    {
        if ($q['answer_type'] === 'SORT') {
            return $this->correctAnswerFormat($q, 'sort');
        }

        if (isset($q['correct_answer']) && !empty($q['correct_answer'])) {
            if (is_array($q['correct_answer'])) {
                return json_encode($q['correct_answer']);
            }

            return $q['correct_answer'];
        }

        return null;
    }

    public function correctAnswerFormat($q, $key, $withoutKey = true)
    {
        if ($withoutKey) {
            return json_encode((!empty($q['options'][$key]) ? array_filter($q['options'][$key]) : new Collection()));
        }

        return json_encode($this->getOptionCollection($q, 'sort'));
    }
}
