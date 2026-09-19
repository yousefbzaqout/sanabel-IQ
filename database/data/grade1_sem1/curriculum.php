<?php

declare(strict_types=1);

/**
 * Grade 1 / Semester 1 curriculum fixtures.
 *
 * Arabic (AR) from official «لغتنا الجميلة — الجزء الأول» TOC.
 * MATH / ISLAM / SOCIAL are Grade-1 Semester-1 aligned starter banks.
 */

if (! function_exists('sanabel_g1_letter_lesson')) {
    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     xp_reward: int,
     *     questions: list<array{type: string, prompt: string, explanation: string, options: list<array{text: string, correct: bool}>}>
     * }
     */
    function sanabel_g1_letter_lesson(string $title, string $letter, string $word, string $correctChoice, string $wrongChoice): array
    {
        return [
            'title' => $title,
            'description' => "نتعلم حرف {$letter} ونقرأه في كلمات قصيرة.",
            'xp_reward' => 45,
            'questions' => [
                [
                    'type' => 'mcq',
                    'prompt' => "أي كلمة تبدأ بحرف {$letter}؟",
                    'explanation' => "كلمة {$word} تبدأ بحرف {$letter}.",
                    'options' => [
                        ['text' => $correctChoice, 'correct' => true],
                        ['text' => $wrongChoice, 'correct' => false],
                        ['text' => '🎈 بالون ملون', 'correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'prompt' => "حرف {$letter} من حروف اللغة العربية الجميلة.",
                    'explanation' => "نعم، حرف {$letter} حرف عربي نقرأه ونكتبه.",
                    'options' => [
                        ['text' => 'صح ✅', 'correct' => true],
                        ['text' => 'خطأ ❌', 'correct' => false],
                    ],
                ],
            ],
        ];
    }
}

if (! function_exists('sanabel_g1_review_lesson')) {
    /**
     * @param  list<string>  $letters
     * @return array{
     *     title: string,
     *     description: string,
     *     xp_reward: int,
     *     questions: list<array{type: string, prompt: string, explanation: string, options: list<array{text: string, correct: bool}>}>
     * }
     */
    function sanabel_g1_review_lesson(string $title, array $letters): array
    {
        $joined = implode(' و', $letters);
        $first = $letters[0];

        return [
            'title' => $title,
            'description' => "مراجعة حروف {$joined}.",
            'xp_reward' => 35,
            'questions' => [
                [
                    'type' => 'mcq',
                    'prompt' => 'أي حرف من حروف مراجعتنا اليوم؟',
                    'explanation' => "حرف {$first} من حروف هذه المراجعة.",
                    'options' => [
                        ['text' => "حرف {$first}", 'correct' => true],
                        ['text' => 'حرف و', 'correct' => false],
                        ['text' => 'حرف ي', 'correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'prompt' => "نستطيع قراءة الحروف {$joined} جهراً بوضوح.",
                    'explanation' => 'المراجعة تساعدنا على القراءة بثقة.',
                    'options' => [
                        ['text' => 'صح ✅', 'correct' => true],
                        ['text' => 'خطأ ❌', 'correct' => false],
                    ],
                ],
            ],
        ];
    }
}

return [
    [
        'code' => 'AR',
        'name' => 'اللغة العربية',
        'slug' => 'arabic-g1',
        'icon' => '📖',
        'description' => 'لغتنا الجميلة — الجزء الأول (الصف الأول، الفصل الأول)',
        'units' => [
            [
                'title' => 'التهيئة',
                'outcomes' => [
                    'التعرف على مرافق المدرسة',
                    'الاستعداد للتعلم والاستماع',
                    'التمييز البصري البسيط',
                ],
                'lessons' => [
                    [
                        'title' => 'التهيئة',
                        'description' => 'أنشطة التهيئة للصف الأول: المدرسة، الاستماع، والتمييز البصري.',
                        'xp_reward' => 40,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'أين نذهب لنتعلم مع الأصدقاء؟',
                                'explanation' => 'نتعلم في المدرسة.',
                                'options' => [
                                    ['text' => '🏫 المدرسة', 'correct' => true],
                                    ['text' => '🛒 السوق', 'correct' => false],
                                    ['text' => '🌳 الحديقة فقط', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'نستمع جيداً عندما يتحدث المعلم.',
                                'explanation' => 'الاستماع مهارة مهمة في الصف.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'أي شيء نضعه في الحقيبة المدرسية؟',
                                'explanation' => 'نضع الكتاب في الحقيبة.',
                                'options' => [
                                    ['text' => '📚 كتاب', 'correct' => true],
                                    ['text' => '⚽ كرة فقط', 'correct' => false],
                                    ['text' => '🍕 طعام ساخن', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'المجموعة الأولى: الراء والدال والباء',
                'outcomes' => [
                    'تمييز حروف الراء والدال والباء',
                    'قراءة كلمات قصيرة بثلاثة أحرف',
                    'كتابة الحروف بخط واضح',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس الأول: حرف الراء', 'ر', 'رمان', '🍎 رمان', '🚗 سيارة'),
                    sanabel_g1_letter_lesson('الدرس الثاني: حرف الدال', 'د', 'دب', '🐻 دب', '🌙 قمر'),
                    sanabel_g1_letter_lesson('الدرس الثالث: حرف الباء', 'ب', 'باب', '🚪 باب', '🍎 تفاحة'),
                    sanabel_g1_review_lesson('مراجعة المجموعة الأولى', ['ر', 'د', 'ب']),
                ],
            ],
            [
                'title' => 'المجموعة الثانية: الميم والنون والسين',
                'outcomes' => [
                    'تمييز حروف الميم والنون والسين',
                    'قراءة كلمات وجمل قصيرة',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس الرابع: حرف الميم', 'م', 'ماء', '💧 ماء', '🏀 كرة'),
                    sanabel_g1_letter_lesson('الدرس الخامس: حرف النون', 'ن', 'نور', '⭐ نور', '🧀 جبن'),
                    sanabel_g1_letter_lesson('الدرس السادس: حرف السين', 'س', 'سمك', '🐟 سمك', '🌹 وردة'),
                    sanabel_g1_review_lesson('مراجعة المجموعة الثانية', ['م', 'ن', 'س']),
                ],
            ],
            [
                'title' => 'المجموعة الثالثة: الزاي والحاء واللام',
                'outcomes' => [
                    'تمييز حروف الزاي والحاء واللام',
                    'تكوين كلمات بسيطة قراءةً وكتابةً',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس السابع: حرف الزاي', 'ز', 'زرع', '🌱 زرع', '🧊 ثلج'),
                    sanabel_g1_letter_lesson('الدرس الثامن: حرف الحاء', 'ح', 'حوت', '🐋 حوت', '🚲 دراجة'),
                    sanabel_g1_letter_lesson('الدرس التاسع: حرف اللام', 'ل', 'لبن', '🥛 لبن', '☀️ شمس'),
                    sanabel_g1_review_lesson('مراجعة المجموعة الثالثة', ['ز', 'ح', 'ل']),
                ],
            ],
            [
                'title' => 'المجموعة الرابعة: التاء والجيم والفاء',
                'outcomes' => [
                    'تمييز حروف التاء والجيم والفاء',
                    'قراءة جمل من كلمتين إلى أربع كلمات',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس العاشر: حرف التاء', 'ت', 'تمر', '🌴 تمر', '🐶 كلب'),
                    sanabel_g1_letter_lesson('الدرس الحادي عشر: حرف الجيم', 'ج', 'جمل', '🐪 جمل', '🎩 قبعة'),
                    sanabel_g1_letter_lesson('الدرس الثاني عشر: حرف الفاء', 'ف', 'فيل', '🐘 فيل', '🌸 زهرة'),
                    sanabel_g1_review_lesson('مراجعة المجموعة الرابعة', ['ت', 'ج', 'ف']),
                ],
            ],
            [
                'title' => 'المجموعة الخامسة: العين والشين والصاد',
                'outcomes' => [
                    'تمييز حروف العين والشين والصاد',
                    'الاستماع لقصص الحروف والتفاعل معها',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس الثالث عشر: حرف العين', 'ع', 'عين', '👁️ عين', '🍬 حلوى'),
                    sanabel_g1_letter_lesson('الدرس الرابع عشر: حرف الشين', 'ش', 'شمس', '☀️ شمس', '🧊 مكعب'),
                    sanabel_g1_letter_lesson('الدرس الخامس عشر: حرف الصاد', 'ص', 'صقر', '🦅 صقر', '🧁 كعكة'),
                    sanabel_g1_review_lesson('مراجعة المجموعة الخامسة', ['ع', 'ش', 'ص']),
                ],
            ],
            [
                'title' => 'المجموعة السادسة: القاف والثاء والخاء',
                'outcomes' => [
                    'تمييز حروف القاف والثاء والخاء',
                    'إتمام حروف الجزء الأول قراءةً وكتابةً',
                ],
                'lessons' => [
                    sanabel_g1_letter_lesson('الدرس السادس عشر: حرف القاف', 'ق', 'قلم', '✏️ قلم', '🎈 بالون'),
                    sanabel_g1_letter_lesson('الدرس السابع عشر: حرف الثاء', 'ث', 'ثلج', '❄️ ثلج', '🍇 عنب'),
                    sanabel_g1_letter_lesson('الدرس الثامن عشر: حرف الخاء', 'خ', 'خبز', '🍞 خبز', '🦋 فراشة'),
                    sanabel_g1_review_lesson('مراجعة المجموعة السادسة', ['ق', 'ث', 'خ']),
                ],
            ],
        ],
    ],
    [
        'code' => 'MATH',
        'name' => 'الرياضيات',
        'slug' => 'math-g1',
        'icon' => '🔢',
        'description' => 'رياضيات الصف الأول — الفصل الأول: العد والأشكال',
        'units' => [
            [
                'title' => 'العد حتى عشرة',
                'outcomes' => [
                    'العد حتى ١٠',
                    'التعرف على الأعداد من ١ إلى ١٠',
                ],
                'lessons' => [
                    [
                        'title' => 'العدد 3',
                        'description' => 'نتعرف على العدد ثلاثة: العد، التمثيل، والكتابة.',
                        'xp_reward' => 50,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'كم تفاحة هنا؟ 🍎🍎🍎',
                                'explanation' => 'هناك ثلاث تفاحات.',
                                'options' => [
                                    ['text' => '٢', 'correct' => false],
                                    ['text' => '٣', 'correct' => true],
                                    ['text' => '٤', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'ما العدد الذي يأتي بعد اثنين؟',
                                'explanation' => 'بعد اثنين يأتي ثلاثة.',
                                'options' => [
                                    ['text' => '١', 'correct' => false],
                                    ['text' => '٣', 'correct' => true],
                                    ['text' => '٥', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'العدد 3 يساوي ثلاث كرات ⚽⚽⚽.',
                                'explanation' => 'نعم، ثلاث كرات تعني العدد 3.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'العد حتى ١٠',
                        'description' => 'نعد الأشياء من واحد إلى عشرة.',
                        'xp_reward' => 50,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'كم تفاحة هنا؟ 🍎🍎🍎',
                                'explanation' => 'هناك ثلاث تفاحات.',
                                'options' => [
                                    ['text' => '٢', 'correct' => false],
                                    ['text' => '٣', 'correct' => true],
                                    ['text' => '٥', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'ما العدد الذي يأتي بعد سبعة؟',
                                'explanation' => 'بعد سبعة يأتي ثمانية.',
                                'options' => [
                                    ['text' => '٦', 'correct' => false],
                                    ['text' => '٨', 'correct' => true],
                                    ['text' => '٩', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'العدد خمسة أكبر من العدد ثلاثة.',
                                'explanation' => 'خمسة أكبر من ثلاثة.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'الأشكال الهندسية البسيطة',
                'outcomes' => [
                    'تمييز الدائرة والمربع والمثلث',
                ],
                'lessons' => [
                    [
                        'title' => 'الأشكال الهندسية البسيطة',
                        'description' => 'نتعرف على الدائرة والمربع والمثلث.',
                        'xp_reward' => 50,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'أي شكل هو دائرة؟',
                                'explanation' => 'الدائرة مستديرة مثل الكرة.',
                                'options' => [
                                    ['text' => '⚪ دائرة', 'correct' => true],
                                    ['text' => '⬛ مربع', 'correct' => false],
                                    ['text' => '🔺 مثلث', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'كم ضلعاً للمثلث؟',
                                'explanation' => 'للمثلث ثلاثة أضلاع.',
                                'options' => [
                                    ['text' => '٢', 'correct' => false],
                                    ['text' => '٣', 'correct' => true],
                                    ['text' => '٤', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'ISLAM',
        'name' => 'التربية الإسلامية',
        'slug' => 'islam-g1',
        'icon' => '🕌',
        'description' => 'التربية الإسلامية — الصف الأول، الفصل الأول',
        'units' => [
            [
                'title' => 'آداب الإسلام للمبتدئين',
                'outcomes' => [
                    'قول بسم الله قبل الطعام',
                    'التعرف على السلام والتحية',
                ],
                'lessons' => [
                    [
                        'title' => 'سورة الفاتحة',
                        'description' => 'نتعرّف على سورة الفاتحة ونبدأ بالبسملة.',
                        'xp_reward' => 50,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'بمَ نبدأ قراءة القرآن؟',
                                'explanation' => 'نبدأ ببسم الله الرحمن الرحيم.',
                                'options' => [
                                    ['text' => 'بسم الله', 'correct' => true],
                                    ['text' => 'إلى اللقاء', 'correct' => false],
                                    ['text' => 'صباح الخير فقط', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'ما اسم السورة التي نقرأها في كل صلاة؟',
                                'explanation' => 'نقرأ سورة الفاتحة في كل صلاة.',
                                'options' => [
                                    ['text' => 'سورة الفاتحة', 'correct' => true],
                                    ['text' => 'سورة قصيرة أخرى فقط', 'correct' => false],
                                    ['text' => 'لا نقرأ شيئاً', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'الحمد لله رب العالمين من آيات سورة الفاتحة.',
                                'explanation' => 'نعم، هذه من آيات الفاتحة.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'بسم الله وآداب الطعام',
                        'description' => 'نتعلم قول بسم الله قبل الأكل.',
                        'xp_reward' => 40,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'ماذا نقول قبل أن نأكل؟',
                                'explanation' => 'نقول بسم الله.',
                                'options' => [
                                    ['text' => 'بسم الله', 'correct' => true],
                                    ['text' => 'إلى اللقاء', 'correct' => false],
                                    ['text' => 'صباح الخير فقط', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'نغسل يدينا قبل الطعام وبعده.',
                                'explanation' => 'نظافة اليدين من الآداب الجميلة.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'السلام والتحية',
                        'description' => 'نلقي السلام على الآخرين.',
                        'xp_reward' => 40,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'ماذا نقول عندما نقابل صديقاً؟',
                                'explanation' => 'نقول السلام عليكم.',
                                'options' => [
                                    ['text' => 'السلام عليكم', 'correct' => true],
                                    ['text' => 'اذهب بعيداً', 'correct' => false],
                                    ['text' => 'لا أتكلم', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SOCIAL',
        'name' => 'التنشئة الوطنية والاجتماعية',
        'slug' => 'social-g1',
        'icon' => '🇵🇸',
        'description' => 'التنشئة الوطنية والاجتماعية — الصف الأول، الفصل الأول',
        'units' => [
            [
                'title' => 'أسرتي ومدرستي ووطني',
                'outcomes' => [
                    'التعرف على أفراد الأسرة',
                    'حب الوطن والعلم الفلسطيني',
                ],
                'lessons' => [
                    [
                        'title' => 'أسرتي الغالية',
                        'description' => 'نتعرف على أفراد الأسرة ونحبهم.',
                        'xp_reward' => 40,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'من يساعدك في البيت ويحبك كثيراً؟',
                                'explanation' => 'الأسرة تحبنا وتساعدنا.',
                                'options' => [
                                    ['text' => '👨‍👩‍👧‍👦 أسرتي', 'correct' => true],
                                    ['text' => '🚗 سيارة غريبة', 'correct' => false],
                                    ['text' => '📦 صندوق فارغ', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'نساعد أمي وأبي في أعمال البيت البسيطة.',
                                'explanation' => 'المساعدة سلوك جميل.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'علم بلادي فلسطين',
                        'description' => 'نتعرّف على علم فلسطين وألوانه ومعانيه.',
                        'xp_reward' => 50,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'ما اسم وطننا الحبيب؟',
                                'explanation' => 'وطننا هو فلسطين.',
                                'options' => [
                                    ['text' => 'فلسطين 🇵🇸', 'correct' => true],
                                    ['text' => 'القمر', 'correct' => false],
                                    ['text' => 'الغابة', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'mcq',
                                'prompt' => 'أي لون يظهر في مثلث علم فلسطين؟',
                                'explanation' => 'المثلث في العلم أحمر.',
                                'options' => [
                                    ['text' => 'أحمر 🔺', 'correct' => true],
                                    ['text' => 'أزرق', 'correct' => false],
                                    ['text' => 'أصفر', 'correct' => false],
                                ],
                            ],
                            [
                                'type' => 'true_false',
                                'prompt' => 'علم فلسطين فيه أسود وأبيض وأخضر وأحمر.',
                                'explanation' => 'نعم، هذه ألوان العلم الفلسطيني.',
                                'options' => [
                                    ['text' => 'صح ✅', 'correct' => true],
                                    ['text' => 'خطأ ❌', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'علمي ووطني',
                        'description' => 'نحب فلسطين ونحترم العلم.',
                        'xp_reward' => 40,
                        'questions' => [
                            [
                                'type' => 'mcq',
                                'prompt' => 'ما اسم وطننا الحبيب؟',
                                'explanation' => 'وطننا هو فلسطين.',
                                'options' => [
                                    ['text' => 'فلسطين 🇵🇸', 'correct' => true],
                                    ['text' => 'القمر', 'correct' => false],
                                    ['text' => 'الغابة', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
