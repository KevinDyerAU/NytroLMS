<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LLNDTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating LLND test data...');

        // Create courses with different categories and main course flags
        $courses = [
            // Non-excluded category, main course (should have LLND logic)
            [
                'title' => 'Business Management Fundamentals',
                'category' => 'business',
                'is_main_course' => 1,
                'description' => 'A comprehensive course on business management principles',
                'lessons_count' => 3,
                'topics_per_lesson' => 2,
                'quizzes_per_topic' => 1,
            ],
            [
                'title' => 'Digital Marketing Essentials',
                'category' => 'marketing',
                'is_main_course' => 1,
                'description' => 'Learn the fundamentals of digital marketing',
                'lessons_count' => 4,
                'topics_per_lesson' => 3,
                'quizzes_per_topic' => 1,
            ],
            [
                'title' => 'Project Management Professional',
                'category' => 'project_management',
                'is_main_course' => 1,
                'description' => 'Advanced project management techniques and methodologies',
                'lessons_count' => 5,
                'topics_per_lesson' => 2,
                'quizzes_per_topic' => 2,
            ],
            // Non-excluded category, non-main course (should have LLND logic but not main course benefits)
            [
                'title' => 'Introduction to Web Development',
                'category' => 'technology',
                'is_main_course' => 0,
                'description' => 'Basic web development concepts for beginners',
                'lessons_count' => 2,
                'topics_per_lesson' => 1,
                'quizzes_per_topic' => 1,
            ],
            // Excluded category, main course (should skip LLND logic)
            [
                'title' => 'Non-Accredited Safety Training',
                'category' => 'non_accredited',
                'is_main_course' => 1,
                'description' => 'Safety training that does not require LLND assessment',
                'lessons_count' => 2,
                'topics_per_lesson' => 1,
                'quizzes_per_topic' => 1,
            ],
            [
                'title' => 'Accelerator Program - Fast Track',
                'category' => 'accelerator',
                'is_main_course' => 1,
                'description' => 'Fast-track program that bypasses LLND requirements',
                'lessons_count' => 3,
                'topics_per_lesson' => 2,
                'quizzes_per_topic' => 1,
            ],
            // Excluded category, non-main course (should skip LLND logic)
            [
                'title' => 'Non-Accredited Workshop',
                'category' => 'non_accredited',
                'is_main_course' => 0,
                'description' => 'Workshop that does not require LLND assessment',
                'lessons_count' => 1,
                'topics_per_lesson' => 1,
                'quizzes_per_topic' => 1,
            ],
        ];

        foreach ($courses as $courseData) {
            $this->command->info("Creating course: {$courseData['title']} (Category: {$courseData['category']}, Main: {$courseData['is_main_course']})");

            $course = Course::create([
                'title' => $courseData['title'],
                'slug' => Str::slug($courseData['title']),
                'category' => $courseData['category'],
                'is_main_course' => $courseData['is_main_course'],
                'course_type' => 'PAID',
                'course_length_days' => 90,
                'next_course_after_days' => 0,
                'next_course' => null,
                'auto_register_next_course' => false,
                'visibility' => 'PUBLIC',
                'status' => 'PUBLISHED',
                'is_archived' => false,
            ]);

            // Create lessons for this course
            for ($lessonIndex = 0; $lessonIndex <= $courseData['lessons_count']; $lessonIndex++) {
                $lesson = Lesson::create([
                    'course_id' => $course->id,
                    'title' => "Lesson {$lessonIndex}: " . $this->getLessonTitle($courseData['category'], $lessonIndex),
                    'slug' => Str::slug("lesson-{$lessonIndex}"),
                    'order' => $lessonIndex,
                    'has_topic' => true,
                ]);

                // Create topics for this lesson
                for ($topicIndex = 0; $topicIndex <= $courseData['topics_per_lesson']; $topicIndex++) {
                    $topic = Topic::create([
                        'lesson_id' => $lesson->id,
                        'course_id' => $course->id,
                        'title' => "Topic {$topicIndex}: " . $this->getTopicTitle($courseData['category'], $lessonIndex, $topicIndex),
                        'slug' => Str::slug("topic-{$topicIndex}"),
                        'order' => $topicIndex,
                        'estimated_time' => rand(15, 45),
                        'has_quiz' => true,
                    ]);

                    // Create quizzes for this topic
                    for ($quizIndex = 0; $quizIndex <= $courseData['quizzes_per_topic']; $quizIndex++) {
                        $quiz = Quiz::create([
                            'topic_id' => $topic->id,
                            'lesson_id' => $lesson->id,
                            'course_id' => $course->id,
                            'title' => "Quiz {$quizIndex}: " . $this->getQuizTitle($courseData['category'], $lessonIndex, $topicIndex, $quizIndex),
                            'slug' => Str::slug("quiz-{$quizIndex}"),
                            'passing_percentage' => 0,
                            'estimated_time' => rand(10, 30),
                            'allowed_attempts' => 3,
                            'order' => $quizIndex,
                            'is_lln' => false, // These are regular quizzes, not LLND quizzes
                        ]);

                        // Create questions for this quiz
                        $this->createQuestionsForQuiz($quiz, $courseData['category']);
                    }
                }
            }
        }

        // Create LLND quiz content (if not exists)
        $this->createLLNDContent();

        $this->command->info('LLND test data created successfully!');
        $this->command->info('');
        $this->command->info('Test Courses Created:');
        $this->command->info('===================');
        $this->command->info('1. Business Management Fundamentals (business, main) - Should have LLND logic');
        $this->command->info('2. Digital Marketing Essentials (marketing, main) - Should have LLND logic');
        $this->command->info('3. Project Management Professional (project_management, main) - Should have LLND logic');
        $this->command->info('4. Introduction to Web Development (technology, non-main) - Should have LLND logic but not main course benefits');
        $this->command->info('5. Non-Accredited Safety Training (non_accredited, main) - Should skip LLND logic');
        $this->command->info('6. Accelerator Program - Fast Track (accelerator, main) - Should skip LLND logic');
        $this->command->info('7. Non-Accredited Workshop (non_accredited, non-main) - Should skip LLND logic');
        $this->command->info('');
        $this->command->info('LLND Quiz ID: ' . config('lln.quiz_id'));
        $this->command->info('PTR Quiz ID: ' . config('ptr.quiz_id'));
    }

    /**
     * Create LLND and PTR content if they don't exist.
     */
    private function createLLNDContent()
    {
        $llnQuizId = config('lln.quiz_id');
        $ptrQuizId = config('ptr.quiz_id');

        // Check if LLND quiz exists
        if (!Quiz::find($llnQuizId)) {
            $this->command->info('Creating LLND quiz content...');

            // Create LLND course
            $llnCourse = Course::firstOrCreate(
                ['id' => config('lln.course_id')],
                [
                    'title' => 'LLND Assessment Course',
                    'slug' => 'llnd-assessment-course',
                    'category' => 'assessment',
                    'is_main_course' => 0,
                    'course_type' => 'FREE',
                    'course_length_days' => 1,
                    'visibility' => 'PUBLIC',
                    'status' => 'PUBLISHED',
                    'is_archived' => false,
                ]
            );

            // Create LLND lesson
            $llnLesson = Lesson::firstOrCreate(
                ['id' => config('lln.lesson_id')],
                [
                    'course_id' => $llnCourse->id,
                    'title' => 'LLND Assessment',
                    'slug' => 'llnd-assessment',
                    'order' => 0,
                    'has_topic' => true,
                ]
            );

            // Create LLND topic
            $llnTopic = Topic::firstOrCreate(
                ['id' => config('lln.topic_id')],
                [
                    'lesson_id' => $llnLesson->id,
                    'course_id' => $llnCourse->id,
                    'title' => 'Language, Literacy and Numeracy Assessment',
                    'slug' => 'llnd-assessment-topic',
                    'order' => 0,
                    'estimated_time' => 30,
                    'has_quiz' => true,
                ]
            );

            // Create LLND quiz
            $llnQuiz = Quiz::firstOrCreate(
                ['id' => $llnQuizId],
                [
                    'topic_id' => $llnTopic->id,
                    'lesson_id' => $llnLesson->id,
                    'course_id' => $llnCourse->id,
                    'title' => 'Language, Literacy, Numeracy and Digital (LLND) Assessment',
                    'slug' => 'llnd-assessment',
                    'passing_percentage' => 0,
                    'estimated_time' => 30,
                    'allowed_attempts' => 999,
                    'order' => 0,
                    'is_lln' => true,
                ]
            );

            // Create LLND questions
            $this->createLLNDQuestions($llnQuiz);
        }

        // Check if PTR quiz exists
        if (!Quiz::find($ptrQuizId)) {
            $this->command->info('Creating PTR quiz content...');

            // Create PTR course
            $ptrCourse = Course::firstOrCreate(
                ['id' => config('ptr.course_id')],
                [
                    'title' => 'Pre-Training Review Course',
                    'slug' => 'ptr-assessment-course',
                    'category' => 'assessment',
                    'is_main_course' => 0,
                    'course_type' => 'FREE',
                    'course_length_days' => 1,
                    'visibility' => 'PUBLIC',
                    'status' => 'PUBLISHED',
                    'is_archived' => false,
                ]
            );

            // Create PTR lesson
            $ptrLesson = Lesson::firstOrCreate(
                ['id' => config('ptr.lesson_id')],
                [
                    'course_id' => $ptrCourse->id,
                    'title' => 'Pre-Training Review',
                    'slug' => 'ptr-assessment',
                    'order' => 0,
                    'has_topic' => true,
                ]
            );

            // Create PTR topic
            $ptrTopic = Topic::firstOrCreate(
                ['id' => config('ptr.topic_id')],
                [
                    'lesson_id' => $ptrLesson->id,
                    'course_id' => $ptrCourse->id,
                    'title' => 'Pre-Training Review Assessment',
                    'slug' => 'ptr-assessment-topic',
                    'order' => 0,
                    'estimated_time' => 20,
                    'has_quiz' => true,
                ]
            );

            // Create PTR quiz
            $ptrQuiz = Quiz::firstOrCreate(
                ['id' => $ptrQuizId],
                [
                    'topic_id' => $ptrTopic->id,
                    'lesson_id' => $ptrLesson->id,
                    'course_id' => $ptrCourse->id,
                    'title' => 'Pre-Training Review Assessment',
                    'slug' => 'ptr-assessment',
                    'passing_percentage' => 0,
                    'estimated_time' => 20,
                    'allowed_attempts' => 999,
                    'order' => 0,
                    'is_lln' => false,
                ]
            );

            // Create PTR questions
            $this->createPTRQuestions($ptrQuiz);
        }
    }

    /**
     * Create questions for regular quizzes.
     */
    private function createQuestionsForQuiz($quiz, $category)
    {
        $questionTypes = ['ESSAY', 'ESSAY', 'SINGLE'];
        $questionCount = rand(3, 5);

        for ($i = 1; $i <= $questionCount; $i++) {
            $questionType = $questionTypes[array_rand($questionTypes)];

            $questionData = [
                'quiz_id' => $quiz->id,
                'title' => "Question {$i}: " . $this->getQuestionTitle($category, $i),
                'slug' => Str::slug("question-{$i}"),
                'content' => $this->getQuestionContent($category, $i),
                'answer_type' => $questionType,
                'order' => $i,
                'required' => true,
                'estimated_time' => rand(2, 5),
            ];

            if ($questionType === 'multiple_choice') {
                $questionData['options'] = json_encode([
                    'A' => 'Option A',
                    'B' => 'Option B',
                    'C' => 'Option C',
                    'D' => 'Option D',
                ]);
                $questionData['correct_answer'] = 'A';
            } elseif ($questionType === 'true_false') {
                $questionData['options'] = json_encode([
                    'true' => 'True',
                    'false' => 'False',
                ]);
                $questionData['correct_answer'] = 'true';
            }

            Question::create($questionData);
        }
    }

    /**
     * Create LLND-specific questions.
     */
    private function createLLNDQuestions($quiz)
    {
        $llnQuestions = [
            [
                'title' => 'Language Assessment Question 1',
                'content' => 'Which of the following is a correct sentence?',
                'answer_type' => 'multiple_choice',
                'options' => json_encode([
                    'A' => 'Me going to store',
                    'B' => 'I am going to the store',
                    'C' => 'Going store me',
                    'D' => 'Store going me',
                ]),
                'correct_answer' => 'B',
            ],
            [
                'title' => 'Literacy Assessment Question 1',
                'content' => 'What does the word "comprehensive" mean?',
                'answer_type' => 'multiple_choice',
                'options' => json_encode([
                    'A' => 'Short and brief',
                    'B' => 'Complete and thorough',
                    'C' => 'Difficult to understand',
                    'D' => 'Outdated information',
                ]),
                'correct_answer' => 'B',
            ],
            [
                'title' => 'Numeracy Assessment Question 1',
                'content' => 'If a course costs $150 and you get a 20% discount, how much do you pay?',
                'answer_type' => 'multiple_choice',
                'options' => json_encode([
                    'A' => '$120',
                    'B' => '$130',
                    'C' => '$140',
                    'D' => '$160',
                ]),
                'correct_answer' => 'A',
            ],
        ];

        foreach ($llnQuestions as $index => $questionData) {
            Question::create(array_merge($questionData, [
                'quiz_id' => $quiz->id,
                'slug' => Str::slug($questionData['title']),
                'order' => $index + 1,
                'required' => true,
                'estimated_time' => 5,
            ]));
        }
    }

    /**
     * Create PTR-specific questions.
     */
    private function createPTRQuestions($quiz)
    {
        $ptrQuestions = [
            [
                'title' => 'Pre-Training Review Question 1',
                'content' => 'Have you completed any similar training in the past?',
                'answer_type' => 'true_false',
                'options' => json_encode([
                    'true' => 'Yes',
                    'false' => 'No',
                ]),
                'correct_answer' => 'true',
            ],
            [
                'title' => 'Pre-Training Review Question 2',
                'content' => 'What is your current skill level in this area?',
                'answer_type' => 'multiple_choice',
                'options' => json_encode([
                    'A' => 'Beginner',
                    'B' => 'Intermediate',
                    'C' => 'Advanced',
                    'D' => 'Expert',
                ]),
                'correct_answer' => 'A',
            ],
        ];

        foreach ($ptrQuestions as $index => $questionData) {
            Question::create(array_merge($questionData, [
                'quiz_id' => $quiz->id,
                'slug' => Str::slug($questionData['title']),
                'order' => $index + 1,
                'required' => true,
                'estimated_time' => 3,
            ]));
        }
    }

    /**
     * Get lesson titles based on category.
     */
    private function getLessonTitle($category, $lessonIndex)
    {
        $titles = [
            'business' => [
                'Introduction to Business Concepts',
                'Business Planning and Strategy',
                'Financial Management Basics',
            ],
            'marketing' => [
                'Marketing Fundamentals',
                'Digital Marketing Channels',
                'Content Marketing Strategy',
                'Marketing Analytics',
            ],
            'project_management' => [
                'Project Initiation',
                'Project Planning',
                'Project Execution',
                'Project Monitoring',
                'Project Closure',
            ],
            'technology' => [
                'Web Development Basics',
                'HTML and CSS Fundamentals',
            ],
            'non_accredited' => [
                'Safety Guidelines',
                'Emergency Procedures',
            ],
            'accelerator' => [
                'Fast Track Introduction',
                'Accelerated Learning Methods',
                'Quick Assessment',
            ],
        ];

        $categoryTitles = $titles[$category] ?? ['General Lesson'];

        return $categoryTitles[$lessonIndex - 1] ?? "Lesson {$lessonIndex}";
    }

    /**
     * Get topic titles based on category and lesson.
     */
    private function getTopicTitle($category, $lessonIndex, $topicIndex)
    {
        $titles = [
            'business' => [
                'Business Environment Analysis',
                'Strategic Planning Process',
                'Financial Statements',
                'Budget Management',
                'Risk Assessment',
                'Business Ethics',
            ],
            'marketing' => [
                'Marketing Mix',
                'Target Audience',
                'Brand Positioning',
                'Social Media Marketing',
                'Email Marketing',
                'SEO Basics',
                'Content Creation',
                'Performance Metrics',
                'ROI Analysis',
                'Marketing Automation',
                'Customer Journey',
                'Conversion Optimization',
            ],
            'project_management' => [
                'Stakeholder Identification',
                'Scope Definition',
                'Work Breakdown Structure',
                'Resource Planning',
                'Timeline Development',
                'Risk Management',
                'Quality Assurance',
                'Communication Planning',
                'Change Management',
                'Lessons Learned',
            ],
            'technology' => [
                'Web Development Overview',
            ],
            'non_accredited' => [
                'Safety Overview',
            ],
            'accelerator' => [
                'Learning Acceleration',
                'Assessment Methods',
            ],
        ];

        $categoryTitles = $titles[$category] ?? ['General Topic'];
        $index = (($lessonIndex - 1) * 2) + ($topicIndex - 1);

        return $categoryTitles[$index] ?? "Topic {$topicIndex}";
    }

    /**
     * Get quiz titles based on category, lesson, topic, and quiz.
     */
    private function getQuizTitle($category, $lessonIndex, $topicIndex, $quizIndex)
    {
        $titles = [
            'business' => [
                'Business Concepts Quiz',
                'Strategic Planning Quiz',
                'Financial Management Quiz',
                'Business Ethics Quiz',
                'Risk Management Quiz',
                'Business Planning Quiz',
            ],
            'marketing' => [
                'Marketing Fundamentals Quiz',
                'Digital Channels Quiz',
                'Content Strategy Quiz',
                'Marketing Analytics Quiz',
                'Social Media Quiz',
                'Email Marketing Quiz',
                'SEO Quiz',
                'Content Creation Quiz',
                'Performance Metrics Quiz',
                'ROI Analysis Quiz',
                'Marketing Automation Quiz',
                'Customer Journey Quiz',
            ],
            'project_management' => [
                'Project Initiation Quiz',
                'Stakeholder Management Quiz',
                'Scope Management Quiz',
                'Planning Quiz',
                'Execution Quiz',
                'Monitoring Quiz',
                'Quality Quiz',
                'Communication Quiz',
                'Change Management Quiz',
                'Project Closure Quiz',
            ],
            'technology' => [
                'Web Development Quiz',
            ],
            'non_accredited' => [
                'Safety Quiz',
            ],
            'accelerator' => [
                'Acceleration Quiz',
                'Assessment Quiz',
            ],
        ];

        $categoryTitles = $titles[$category] ?? ['General Quiz'];
        $index = (($lessonIndex - 1) * 2) + ($topicIndex - 1);

        return $categoryTitles[$index] ?? "Quiz {$quizIndex}";
    }

    /**
     * Get question titles based on category.
     */
    private function getQuestionTitle($category, $questionIndex)
    {
        $titles = [
            'business' => [
                'Business Environment Question',
                'Strategic Planning Question',
                'Financial Management Question',
                'Business Ethics Question',
                'Risk Management Question',
            ],
            'marketing' => [
                'Marketing Fundamentals Question',
                'Digital Marketing Question',
                'Content Strategy Question',
                'Marketing Analytics Question',
                'Social Media Question',
            ],
            'project_management' => [
                'Project Initiation Question',
                'Planning Question',
                'Execution Question',
                'Monitoring Question',
                'Closure Question',
            ],
            'technology' => [
                'Web Development Question',
                'HTML Question',
                'CSS Question',
            ],
            'non_accredited' => [
                'Safety Question',
                'Emergency Question',
            ],
            'accelerator' => [
                'Acceleration Question',
                'Learning Question',
            ],
        ];

        $categoryTitles = $titles[$category] ?? ['General Question'];

        return $categoryTitles[$questionIndex - 1] ?? "Question {$questionIndex}";
    }

    /**
     * Get question content based on category.
     */
    private function getQuestionContent($category, $questionIndex)
    {
        $content = [
            'business' => [
                'What is the primary purpose of a business plan?',
                'Which of the following is a key component of strategic planning?',
                'What does ROI stand for in business?',
                'Why is business ethics important?',
                'What is risk management in business?',
            ],
            'marketing' => [
                'What are the 4 Ps of marketing?',
                'Which platform is best for B2B marketing?',
                'What is content marketing?',
                'How do you measure marketing success?',
                'What is social media marketing?',
            ],
            'project_management' => [
                'What is the first phase of project management?',
                'What is a stakeholder?',
                'What is scope creep?',
                'What is the critical path?',
                'What is a milestone?',
            ],
            'technology' => [
                'What is HTML?',
                'What does CSS stand for?',
                'What is responsive design?',
            ],
            'non_accredited' => [
                'What should you do in case of fire?',
                'What is the emergency number?',
            ],
            'accelerator' => [
                'What is accelerated learning?',
                'How does fast-track learning work?',
            ],
        ];

        $categoryContent = $content[$category] ?? ['General question content'];

        return $categoryContent[$questionIndex - 1] ?? "This is question {$questionIndex} content.";
    }
}
