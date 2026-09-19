<?php

declare(strict_types=1);

namespace App\Support\Curriculum\MockDemo;

/**
 * Pedagogical topic templates for demo/marketing curriculum (grades 1–6).
 */
final class MockDemoTopicCatalog
{
    /**
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     unit_title: string,
     *     glyph: string,
     *     focus_words: list<string>,
     *     emojis: list<string>,
     *     summary: string
     * }>
     */
    public function topicsFor(int $grade, string $subject, int $semester): array
    {
        $subject = strtolower($subject);
        $pool = match ($subject) {
            'arabic' => $this->arabicTopics($grade),
            'math' => $this->mathTopics($grade),
            'science' => $this->scienceTopics($grade),
            default => throw new \InvalidArgumentException("Unsupported subject: {$subject}"),
        };

        $semesterUnits = array_values(array_filter(
            $pool,
            static fn (array $topic): bool => (int) ($topic['semester'] ?? 1) === $semester,
        ));

        if ($semesterUnits === []) {
            $semesterUnits = $pool;
        }

        return array_map(static function (array $topic) use ($semester): array {
            unset($topic['semester']);

            return $topic + ['unit_title' => $topic['unit_title'] ?? 'وحدة تجريبية'];
        }, $semesterUnits);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function arabicTopics(int $grade): array
    {
        if ($grade === 1) {
            return [
                ['semester' => 1, 'slug' => 'letter-alif', 'title' => 'حرف الألف', 'unit_title' => 'الحروف والحركات', 'glyph' => 'أ', 'focus_words' => ['أسد', 'أرنب', 'أمل'], 'emojis' => ['🦁', '🐰', '🌟'], 'summary' => 'نتعرّف على حرف الألف بحركاته.'],
                ['semester' => 1, 'slug' => 'letter-baa', 'title' => 'حرف الباء', 'unit_title' => 'الحروف والحركات', 'glyph' => 'ب', 'focus_words' => ['باب', 'بحر', 'بيت'], 'emojis' => ['🚪', '🌊', '🏠'], 'summary' => 'نتعلم حرف الباء بالفتح والضم والكسر.'],
                ['semester' => 1, 'slug' => 'letter-taa', 'title' => 'حرف التاء', 'unit_title' => 'الحروف والحركات', 'glyph' => 'ت', 'focus_words' => ['تمر', 'تاج', 'توت'], 'emojis' => ['🌴', '👑', '🫐'], 'summary' => 'نميّز صوت التاء في بداية الكلمات.'],
                ['semester' => 2, 'slug' => 'letter-jeem', 'title' => 'حرف الجيم', 'unit_title' => 'المقاطع القصيرة', 'glyph' => 'ج', 'focus_words' => ['جمل', 'جبن', 'جرس'], 'emojis' => ['🐪', '🧀', '🔔'], 'summary' => 'نركّب مقاطع بحرف الجيم.'],
                ['semester' => 2, 'slug' => 'letter-dal', 'title' => 'حرف الدال', 'unit_title' => 'المقاطع القصيرة', 'glyph' => 'د', 'focus_words' => ['دفتر', 'دب', 'دجاجة'], 'emojis' => ['📓', '🐻', '🐔'], 'summary' => 'نقرأ كلمات تبدأ بالدال.'],
                ['semester' => 2, 'slug' => 'letter-raa', 'title' => 'حرف الراء', 'unit_title' => 'المقاطع القصيرة', 'glyph' => 'ر', 'focus_words' => ['رمان', 'ريح', 'رجل'], 'emojis' => ['🍎', '💨', '🚶'], 'summary' => 'نتدرّب على نطق الراء بوضوح.'],
            ];
        }

        if ($grade <= 3) {
            return [
                ['semester' => 1, 'slug' => 'vocab-school', 'title' => 'مفردات المدرسة', 'unit_title' => 'قاموسي الصغير', 'glyph' => 'م', 'focus_words' => ['مدرسة', 'معلم', 'مقعد'], 'emojis' => ['🏫', '👩‍🏫', '🪑'], 'summary' => 'نوسّع مفردات الصف والمدرسة.'],
                ['semester' => 1, 'slug' => 'vocab-family', 'title' => 'أفراد العائلة', 'unit_title' => 'قاموسي الصغير', 'glyph' => 'ع', 'focus_words' => ['أب', 'أم', 'أخ'], 'emojis' => ['👨', '👩', '👦'], 'summary' => 'نكوّن جملاً قصيرة عن العائلة.'],
                ['semester' => 1, 'slug' => 'short-sentences', 'title' => 'جمل قصيرة', 'unit_title' => 'أقرأ وأفهم', 'glyph' => 'ق', 'focus_words' => ['قرأ', 'كتب', 'لعب'], 'emojis' => ['📖', '✍️', '⚽'], 'summary' => 'نقرأ جملاً من ثلاث إلى خمس كلمات.'],
                ['semester' => 2, 'slug' => 'story-garden', 'title' => 'قصة الحديقة', 'unit_title' => 'قصص قصيرة', 'glyph' => 'ح', 'focus_words' => ['حديقة', 'زهرة', 'ماء'], 'emojis' => ['🌳', '🌸', '💧'], 'summary' => 'نفهم أحداث قصة قصيرة عن الحديقة.'],
                ['semester' => 2, 'slug' => 'opposites', 'title' => 'المتضادات', 'unit_title' => 'قصص قصيرة', 'glyph' => 'ك', 'focus_words' => ['كبير', 'صغير', 'حار'], 'emojis' => ['🐘', '🐭', '🔥'], 'summary' => 'نميّز الكلمات المتضادة.'],
                ['semester' => 2, 'slug' => 'describe', 'title' => 'أصف ما أرى', 'unit_title' => 'تعبير شفوي', 'glyph' => 'و', 'focus_words' => ['لون', 'شكل', 'حجم'], 'emojis' => ['🎨', '🔵', '📏'], 'summary' => 'نصف أشياء مألوفة بجمل واضحة.'],
            ];
        }

        return [
            ['semester' => 1, 'slug' => 'grammar-noun', 'title' => 'الاسم والفعل', 'unit_title' => 'قواعد أساسية', 'glyph' => 'ن', 'focus_words' => ['اسم', 'فعل', 'جملة'], 'emojis' => ['📝', '🏃', '💬'], 'summary' => 'نفرّق بين الاسم والفعل في الجملة.'],
            ['semester' => 1, 'slug' => 'reading-main-idea', 'title' => 'الفكرة الرئيسة', 'unit_title' => 'فهم المقروء', 'glyph' => 'ف', 'focus_words' => ['فكرة', 'عنوان', 'فقرة'], 'emojis' => ['💡', '🏷️', '📄'], 'summary' => 'نستخرج الفكرة الرئيسة من فقرة قصيرة.'],
            ['semester' => 1, 'slug' => 'punctuation', 'title' => 'علامات الترقيم', 'unit_title' => 'قواعد أساسية', 'glyph' => '؟', 'focus_words' => ['نقطة', 'فاصلة', 'سؤال'], 'emojis' => ['⚫', '⏸️', '❓'], 'summary' => 'نستخدم علامات الترقيم الأساسية.'],
            ['semester' => 2, 'slug' => 'comprehension', 'title' => 'أسئلة الفهم', 'unit_title' => 'نصوص قصيرة', 'glyph' => 'س', 'focus_words' => ['لماذا', 'كيف', 'متى'], 'emojis' => ['❓', '🛠️', '⏰'], 'summary' => 'نجيب عن أسئلة فهم بعد قراءة نص.'],
            ['semester' => 2, 'slug' => 'synonyms', 'title' => 'المرادفات', 'unit_title' => 'ثروة لغوية', 'glyph' => 'م', 'focus_words' => ['سعيد', 'فرح', 'مسرور'], 'emojis' => ['😊', '🎉', '😄'], 'summary' => 'نختار مرادفات مناسبة في السياق.'],
            ['semester' => 2, 'slug' => 'summary-writing', 'title' => 'تلخيص فقرة', 'unit_title' => 'نصوص قصيرة', 'glyph' => 'ل', 'focus_words' => ['تلخيص', 'أهم', 'نقاط'], 'emojis' => ['✂️', '⭐', '📌'], 'summary' => 'نلخّص فقرة بجملتين واضحتين.'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mathTopics(int $grade): array
    {
        if ($grade === 1) {
            return [
                ['semester' => 1, 'slug' => 'count-1-3', 'title' => 'العدّ ١–٣', 'unit_title' => 'الأعداد حتى ٩', 'glyph' => '٣', 'focus_words' => ['واحد', 'اثنان', 'ثلاثة'], 'emojis' => ['🍎', '⭐', '🎈'], 'summary' => 'نعدّ مجموعات صغيرة حتى ثلاثة.'],
                ['semester' => 1, 'slug' => 'count-4-6', 'title' => 'العدّ ٤–٦', 'unit_title' => 'الأعداد حتى ٩', 'glyph' => '٦', 'focus_words' => ['أربعة', 'خمسة', 'ستة'], 'emojis' => ['🍊', '🌸', '🦋'], 'summary' => 'نتعرّف على الأعداد من ٤ إلى ٦.'],
                ['semester' => 1, 'slug' => 'count-7-9', 'title' => 'العدّ ٧–٩', 'unit_title' => 'الأعداد حتى ٩', 'glyph' => '٩', 'focus_words' => ['سبعة', 'ثمانية', 'تسعة'], 'emojis' => ['🍇', '🦋', '🎯'], 'summary' => 'نكمل سلسلة الأعداد حتى ٩.'],
                ['semester' => 2, 'slug' => 'compare', 'title' => 'أكبر وأصغر', 'unit_title' => 'مقارنة وترتيب', 'glyph' => '٥', 'focus_words' => ['أكبر', 'أصغر', 'يساوي'], 'emojis' => ['⬆️', '⬇️', '⚖️'], 'summary' => 'نقارن أعداداً بسيطة.'],
                ['semester' => 2, 'slug' => 'order', 'title' => 'الترتيب التصاعدي', 'unit_title' => 'مقارنة وترتيب', 'glyph' => '٤', 'focus_words' => ['أول', 'ثاني', 'ثالث'], 'emojis' => ['1️⃣', '2️⃣', '3️⃣'], 'summary' => 'نرتّب الأعداد من الأصغر للأكبر.'],
                ['semester' => 2, 'slug' => 'shapes-basic', 'title' => 'أشكال بسيطة', 'unit_title' => 'هندسة أولية', 'glyph' => '○', 'focus_words' => ['دائرة', 'مربع', 'مثلث'], 'emojis' => ['⭕', '⬜', '🔺'], 'summary' => 'نميّز الدائرة والمربع والمثلث.'],
            ];
        }

        if ($grade === 2) {
            return [
                ['semester' => 1, 'slug' => 'add-within-10', 'title' => 'جمع حتى ١٠', 'unit_title' => 'الجمع والطرح', 'glyph' => '٨', 'focus_words' => ['جمع', 'مجموع', 'زائد'], 'emojis' => ['➕', '🧮', '🍎'], 'summary' => 'نجمع أعداداً ضمن ١٠.'],
                ['semester' => 1, 'slug' => 'sub-within-10', 'title' => 'طرح حتى ١٠', 'unit_title' => 'الجمع والطرح', 'glyph' => '٧', 'focus_words' => ['طرح', 'باقي', 'ناقص'], 'emojis' => ['➖', '🍪', '🧮'], 'summary' => 'نطرح أعداداً ضمن ١٠.'],
                ['semester' => 1, 'slug' => 'fact-families', 'title' => 'عائلات الحقائق', 'unit_title' => 'الجمع والطرح', 'glyph' => '٦', 'focus_words' => ['عائلة', 'جمع', 'طرح'], 'emojis' => ['👨‍👩‍👧', '➕', '➖'], 'summary' => 'نربط جمل الجمع والطرح.'],
                ['semester' => 2, 'slug' => 'add-within-20', 'title' => 'جمع حتى ٢٠', 'unit_title' => 'أعداد أكبر', 'glyph' => '١٥', 'focus_words' => ['عشرة', 'خمسة عشر', 'عشرين'], 'emojis' => ['🔟', '🖐️', '🎯'], 'summary' => 'نوسّع الجمع حتى ٢٠.'],
                ['semester' => 2, 'slug' => 'word-problems', 'title' => 'مسائل كلامية', 'unit_title' => 'أعداد أكبر', 'glyph' => '٩', 'focus_words' => ['مسألة', 'حل', 'خطوة'], 'emojis' => ['📝', '✅', '👣'], 'summary' => 'نحل مسائل جمع وطرح بسيطة.'],
                ['semester' => 2, 'slug' => 'place-tens', 'title' => 'العشرات والآحاد', 'unit_title' => 'القيمة المكانية', 'glyph' => '١٠', 'focus_words' => ['عشرات', 'آحاد', 'عشرة'], 'emojis' => ['📦', '1️⃣', '🔟'], 'summary' => 'نفرّق بين العشرات والآحاد.'],
            ];
        }

        if ($grade === 3) {
            return [
                ['semester' => 1, 'slug' => 'multiply-intro', 'title' => 'مفهوم الضرب', 'unit_title' => 'الضرب والقسمة', 'glyph' => '٤', 'focus_words' => ['ضرب', 'مرات', 'حاصل'], 'emojis' => ['✖️', '🧩', '🔢'], 'summary' => 'نفهم الضرب كجمع متكرر.'],
                ['semester' => 1, 'slug' => 'times-2-5', 'title' => 'جدول ٢ و٥', 'unit_title' => 'الضرب والقسمة', 'glyph' => '٥', 'focus_words' => ['اثنان', 'خمسة', 'ضعف'], 'emojis' => ['2️⃣', '5️⃣', '⚡'], 'summary' => 'نحفظ حقائق جدول ٢ و٥.'],
                ['semester' => 1, 'slug' => 'divide-intro', 'title' => 'مفهوم القسمة', 'unit_title' => 'الضرب والقسمة', 'glyph' => '٣', 'focus_words' => ['قسمة', 'توزيع', 'نصيب'], 'emojis' => ['➗', '🍪', '👥'], 'summary' => 'نوزّع أشياء بالتساوي.'],
                ['semester' => 2, 'slug' => 'times-3-4', 'title' => 'جدول ٣ و٤', 'unit_title' => 'جداول الضرب', 'glyph' => '١٢', 'focus_words' => ['ثلاثة', 'أربعة', 'اثنا عشر'], 'emojis' => ['3️⃣', '4️⃣', '🎯'], 'summary' => 'نتمرّن على جداول ٣ و٤.'],
                ['semester' => 2, 'slug' => 'mixed-ops', 'title' => 'عمليات مختلطة', 'unit_title' => 'جداول الضرب', 'glyph' => '٨', 'focus_words' => ['جمع', 'ضرب', 'نتيجة'], 'emojis' => ['➕', '✖️', '🏁'], 'summary' => 'نختار العملية المناسبة للمسألة.'],
                ['semester' => 2, 'slug' => 'measure-length', 'title' => 'قياس الطول', 'unit_title' => 'قياس', 'glyph' => 'م', 'focus_words' => ['سنتيمتر', 'متر', 'أطول'], 'emojis' => ['📏', '📐', '🦒'], 'summary' => 'نقارن أطوالاً بوحدات بسيطة.'],
            ];
        }

        return [
            ['semester' => 1, 'slug' => 'fractions-half', 'title' => 'النصف والربع', 'unit_title' => 'الكسور', 'glyph' => '½', 'focus_words' => ['نصف', 'ربع', 'جزء'], 'emojis' => ['🍕', '🍰', '✂️'], 'summary' => 'نتعرّف على النصف والربع.'],
            ['semester' => 1, 'slug' => 'fractions-compare', 'title' => 'مقارنة كسور', 'unit_title' => 'الكسور', 'glyph' => '⅓', 'focus_words' => ['أكبر', 'أصغر', 'متساو'], 'emojis' => ['📊', '⬆️', '⬇️'], 'summary' => 'نقارن كسوراً بسيطة.'],
            ['semester' => 1, 'slug' => 'perimeter', 'title' => 'محيط الشكل', 'unit_title' => 'هندسة', 'glyph' => '□', 'focus_words' => ['محيط', 'ضلع', 'مجموع'], 'emojis' => ['⬛', '📏', '➕'], 'summary' => 'نحسب محيط مستطيل بسيط.'],
            ['semester' => 2, 'slug' => 'area-intro', 'title' => 'المساحة', 'unit_title' => 'هندسة وقياس', 'glyph' => '▣', 'focus_words' => ['مساحة', 'وحدات', 'تغطية'], 'emojis' => ['🟩', '🧱', '📐'], 'summary' => 'نقدر المساحة بعد المربعات.'],
            ['semester' => 2, 'slug' => 'angles', 'title' => 'الزوايا', 'unit_title' => 'هندسة وقياس', 'glyph' => '∠', 'focus_words' => ['زاوية', 'قائمة', 'حادة'], 'emojis' => ['📐', '📏', '✏️'], 'summary' => 'نميّز الزاوية القائمة والحادة.'],
            ['semester' => 2, 'slug' => 'data-pictograph', 'title' => 'رسوم بيانية', 'unit_title' => 'بيانات', 'glyph' => '📊', 'focus_words' => ['رسم', 'أكثر', 'أقل'], 'emojis' => ['📊', '📈', '🍎'], 'summary' => 'نقرأ رسماً بيانياً بسيطاً.'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scienceTopics(int $grade): array
    {
        if ($grade <= 2) {
            return [
                ['semester' => 1, 'slug' => 'senses', 'title' => 'الحواس الخمس', 'unit_title' => 'جسمي والعالم', 'glyph' => 'س', 'focus_words' => ['سمع', 'بصر', 'لمس'], 'emojis' => ['👂', '👁️', '✋'], 'summary' => 'نتعرّف على الحواس وكيف نستكشف بها.'],
                ['semester' => 1, 'slug' => 'plants', 'title' => 'النباتات', 'unit_title' => 'كائنات حية', 'glyph' => 'ن', 'focus_words' => ['جذر', 'ساق', 'ورقة'], 'emojis' => ['🌱', '🌿', '🍃'], 'summary' => 'نسمي أجزاء النبتة البسيطة.'],
                ['semester' => 1, 'slug' => 'animals', 'title' => 'حيوانات أليفة', 'unit_title' => 'كائنات حية', 'glyph' => 'ح', 'focus_words' => ['قطة', 'كلب', 'عصفور'], 'emojis' => ['🐱', '🐶', '🐦'], 'summary' => 'نميّز حيوانات أليفة وموائلها.'],
                ['semester' => 2, 'slug' => 'weather', 'title' => 'الطقس', 'unit_title' => 'الطبيعة من حولنا', 'glyph' => 'ط', 'focus_words' => ['شمس', 'مطر', 'ريح'], 'emojis' => ['☀️', '🌧️', '💨'], 'summary' => 'نصف حالة الطقس اليومية.'],
                ['semester' => 2, 'slug' => 'day-night', 'title' => 'النهار والليل', 'unit_title' => 'الطبيعة من حولنا', 'glyph' => 'ش', 'focus_words' => ['نهار', 'ليل', 'قمر'], 'emojis' => ['🌞', '🌙', '⭐'], 'summary' => 'نفرّق بين النهار والليل.'],
                ['semester' => 2, 'slug' => 'healthy-habits', 'title' => 'عادات صحية', 'unit_title' => 'صحتي', 'glyph' => 'ص', 'focus_words' => ['غسل', 'نوم', 'حركة'], 'emojis' => ['🧼', '😴', '🏃'], 'summary' => 'نختار عادات تحافظ على صحتنا.'],
            ];
        }

        if ($grade <= 4) {
            return [
                ['semester' => 1, 'slug' => 'matter-states', 'title' => 'حالات المادة', 'unit_title' => 'المادة', 'glyph' => 'م', 'focus_words' => ['صلب', 'سائل', 'غاز'], 'emojis' => ['🧊', '💧', '💨'], 'summary' => 'نميّز حالات المادة الثلاث.'],
                ['semester' => 1, 'slug' => 'habitats', 'title' => 'الموائل', 'unit_title' => 'البيئة', 'glyph' => 'غ', 'focus_words' => ['غابة', 'صحراء', 'بحر'], 'emojis' => ['🌲', '🏜️', '🌊'], 'summary' => 'نربط الكائنات بموائلها.'],
                ['semester' => 1, 'slug' => 'water-cycle', 'title' => 'دورة الماء', 'unit_title' => 'المادة', 'glyph' => 'د', 'focus_words' => ['تبخر', 'تكاثف', 'هطول'], 'emojis' => ['♨️', '☁️', '🌧️'], 'summary' => 'نتابع مراحل دورة الماء.'],
                ['semester' => 2, 'slug' => 'food-chains', 'title' => 'سلاسل غذائية', 'unit_title' => 'البيئة والحياة', 'glyph' => 'غ', 'focus_words' => ['منتج', 'مستهلك', 'مفترس'], 'emojis' => ['🌱', '🐰', '🦊'], 'summary' => 'نرتّب سلسلة غذائية بسيطة.'],
                ['semester' => 2, 'slug' => 'magnets', 'title' => 'المغناطيس', 'unit_title' => 'قوى بسيطة', 'glyph' => 'ق', 'focus_words' => ['جذب', 'تنافر', 'قطب'], 'emojis' => ['🧲', '➡️', '⬅️'], 'summary' => 'نجرّب جذب وتنافر المغناطيس.'],
                ['semester' => 2, 'slug' => 'soil', 'title' => 'التربة', 'unit_title' => 'البيئة والحياة', 'glyph' => 'ت', 'focus_words' => ['رمل', 'طين', 'حصى'], 'emojis' => ['🏜️', '🧱', '🪨'], 'summary' => 'نقارن أنواع تربة مختلفة.'],
            ];
        }

        return [
            ['semester' => 1, 'slug' => 'digestive', 'title' => 'الجهاز الهضمي', 'unit_title' => 'جسم الإنسان', 'glyph' => 'ه', 'focus_words' => ['فم', 'معدة', 'هضم'], 'emojis' => ['👄', '🫁', '🍎'], 'summary' => 'نتابع مسار الطعام في الجسم.'],
            ['semester' => 1, 'slug' => 'circulatory', 'title' => 'الجهاز الدوري', 'unit_title' => 'جسم الإنسان', 'glyph' => 'ق', 'focus_words' => ['قلب', 'دم', 'أوعية'], 'emojis' => ['❤️', '🩸', '🔁'], 'summary' => 'نفهم دور القلب في ضخ الدم.'],
            ['semester' => 1, 'slug' => 'energy-forms', 'title' => 'أشكال الطاقة', 'unit_title' => 'الطاقة', 'glyph' => 'ط', 'focus_words' => ['حرارة', 'ضوء', 'حركة'], 'emojis' => ['🔥', '💡', '⚙️'], 'summary' => 'نسمي أشكالاً يومية للطاقة.'],
            ['semester' => 2, 'slug' => 'simple-circuits', 'title' => 'دائرة كهربائية', 'unit_title' => 'كهرباء بسيطة', 'glyph' => 'ك', 'focus_words' => ['بطارية', 'سلك', 'مصباح'], 'emojis' => ['🔋', '🔌', '💡'], 'summary' => 'نركب دائرة بسيطة لإضاءة مصباح.'],
            ['semester' => 2, 'slug' => 'ecosystems', 'title' => 'النظم البيئية', 'unit_title' => 'البيئة', 'glyph' => 'ن', 'focus_words' => ['توازن', 'تنوع', 'موارد'], 'emojis' => ['⚖️', '🦋', '🌍'], 'summary' => 'نفهم أهمية التوازن في البيئة.'],
            ['semester' => 2, 'slug' => 'renewable', 'title' => 'طاقة متجددة', 'unit_title' => 'الطاقة والبيئة', 'glyph' => 'ش', 'focus_words' => ['شمس', 'رياح', 'نظيفة'], 'emojis' => ['☀️', '🌬️', '♻️'], 'summary' => 'نقارن مصادر طاقة متجددة.'],
        ];
    }
}
