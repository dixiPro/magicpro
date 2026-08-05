<?php

namespace MagicProDatabase\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use MagicProDatabaseModels\Feed;
use MagicProDatabaseModels\FeedGroup;

/**
 * Demo data of the "Ленты" module: a group with three feeds — categories,
 * products and news.
 *
 * Between them they cover the whole mechanics: unique strings, numbers, dates,
 * flags, links between feeds, fields living in __data, hidden records and
 * manual order.
 *
 * The seeder does not delete anything. Run twice, it stops and says so — the
 * demo group has to be removed by hand first.
 */
class FeedDemoSeeder extends Seeder
{
    private const GROUP = 'Демо';

    public function run(): void
    {
        if (FeedGroup::where('title', self::GROUP)->exists()) {
            $this->command?->warn(
                'Группа «' . self::GROUP . '» уже есть. Сидер ничего не делает: удалите её вручную и повторите.'
            );

            return;
        }

        $group = FeedGroup::create([
            'title'    => self::GROUP,
            'position' => 1,
        ]);

        $categories = $this->categories($group);
        $products   = $this->products($group, $categories);
        $news       = $this->news($group);

        $this->command?->info(sprintf(
            'Группа «%s»: категорий %d, товаров %d, новостей %d.',
            self::GROUP,
            $categories->count(),
            $products,
            $news
        ));
    }

    /** Simple feed of one field, used as the target of a link. */
    private function categories(FeedGroup $group): \Illuminate\Support\Collection
    {
        $feed = Feed::create([
            'code'     => 'categories',
            'title'    => 'Категории',
            'group_id' => $group->id,
            'position' => 0,
            'schema'   => [
                'version' => 1,
                'fields'  => [
                    [
                        'column'     => '__string_1',
                        'code'       => 'title',
                        'label'      => 'Название',
                        'unique'     => true,
                        'validation' => 'required|string|min:2|max:255',
                    ],
                ],
            ],
        ]);

        $titles = ['Сыры', 'Вино', 'Хлеб', 'Молочное', 'Бакалея'];

        return collect($titles)->mapWithKeys(fn (string $title) => [
            $title => $feed->items()->create([
                'title'     => $title,
                '__visible' => true,
            ]),
        ]);
    }

    /**
     * The main feed: a unique string, a number, a date, a flag, a link to a
     * category and two fields inside __data.
     */
    private function products(FeedGroup $group, \Illuminate\Support\Collection $categories): int
    {
        $categoryFeedId = Feed::where('code', 'categories')->value('id');

        $feed = Feed::create([
            'code'     => 'products',
            'title'    => 'Товары',
            'group_id' => $group->id,
            'position' => 1,
            'schema'   => [
                'version' => 1,
                'fields'  => [
                    [
                        'column'     => '__string_1',
                        'code'       => 'title',
                        'label'      => 'Название',
                        'unique'     => true,
                        'validation' => 'required|string|min:3|max:255',
                    ],
                    [
                        'column'     => '__bigint_1',
                        'code'       => 'price',
                        'label'      => 'Цена, копейки',
                        'validation' => 'required|integer|min:1',
                    ],
                    [
                        'column'     => '__date_1',
                        'code'       => 'expiration',
                        'label'      => 'Годен до',
                        'validation' => 'required|date',
                    ],
                    [
                        'column'     => '__bool_1',
                        'code'       => 'hasDiscount',
                        'label'      => 'Со скидкой',
                        'default'    => false,
                        'validation' => 'required|boolean',
                    ],
                    [
                        'column'   => '__link_1',
                        'code'     => 'category',
                        'label'    => 'Категория',
                        'input'    => 'select',
                        'relation' => [
                            'feed_id'      => $categoryFeedId,
                            'display_code' => '__string_1',
                        ],
                    ],
                    [
                        'column' => '__data',
                        'code'   => 'content',
                        'data'   => [
                            [
                                'type'       => 'string',
                                'code'       => 'subTitle',
                                'label'      => 'Подзаголовок',
                                'validation' => 'nullable|string|max:255',
                            ],
                            [
                                'type'       => 'text',
                                'code'       => 'description',
                                'label'      => 'Описание',
                                'editor'     => 'html',
                                'validation' => 'nullable|string|max:5000',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // название, цена в копейках, категория, срок годности в днях,
        // скидка, видимость, подзаголовок
        $rows = [
            ['Сыр Пармезан 200 г',      99000, 'Сыры',     540, true,  true,  'Выдержка 24 месяца'],
            ['Сыр Гауда 250 г',         41000, 'Сыры',     120, false, true,  'Молодой, сливочный'],
            ['Сыр Бри 150 г',           58000, 'Сыры',      45, false, true,  'С белой плесенью'],
            ['Сыр Дорблю 100 г',        62000, 'Сыры',      60, true,  false, 'С голубой плесенью'],
            ['Моцарелла 125 г',         21000, 'Сыры',      21, false, true,  'В рассоле'],
            ['Вино Кьянти 0,75 л',     129000, 'Вино',    3650, false, true,  'Тоскана, сухое красное'],
            ['Вино Риоха 0,75 л',      145000, 'Вино',    3650, true,  true,  'Испания, выдержка в дубе'],
            ['Вино Пино Гриджо 0,75 л', 98000, 'Вино',    1825, false, true,  'Сухое белое'],
            ['Игристое Просекко 0,75 л', 112000, 'Вино',   1095, false, false, 'Брют'],
            ['Хлеб бородинский',         7500, 'Хлеб',       5, false, true,  'На ржаной закваске'],
            ['Багет французский',        9000, 'Хлеб',       2, false, true,  'Хрустящая корка'],
            ['Чиабатта',                11000, 'Хлеб',       3, true,  true,  'На оливковом масле'],
            ['Хлеб цельнозерновой',      9500, 'Хлеб',       4, false, false, 'Без сахара'],
            ['Молоко 3,2% 1 л',          8900, 'Молочное',   7, false, true,  'Пастеризованное'],
            ['Сливки 20% 500 мл',       15500, 'Молочное',  10, false, true,  'Для соусов'],
            ['Творог 5% 300 г',         12500, 'Молочное',   5, true,  true,  'Мягкий'],
            ['Сметана 15% 400 г',       11000, 'Молочное',  14, false, true,  'Густая'],
            ['Гречка 900 г',            13500, 'Бакалея',  365, false, true,  'Ядрица высший сорт'],
            ['Рис басмати 1 кг',        24000, 'Бакалея',  540, false, true,  'Длиннозёрный'],
            ['Макароны спагетти 500 г',  9900, 'Бакалея',  730, false, true,  'Из твёрдых сортов'],
        ];

        foreach ($rows as [$title, $price, $category, $days, $discount, $visible, $subTitle]) {
            $feed->items()->create([
                'title'       => $title,
                'price'       => $price,
                'expiration'  => Carbon::now()->addDays($days),
                'hasDiscount' => $discount,
                'category'    => $categories[$category]->id,
                'subTitle'    => $subTitle,
                'description' => '<p>' . $title . '. ' . $subTitle . '.</p>',
                '__visible'   => $visible,
            ]);
        }

        return count($rows);
    }

    /** A feed sorted by date rather than by hand. */
    private function news(FeedGroup $group): int
    {
        $feed = Feed::create([
            'code'     => 'news',
            'title'    => 'Новости',
            'group_id' => $group->id,
            'position' => 2,
            'schema'   => [
                'version' => 1,
                'fields'  => [
                    [
                        'column'     => '__string_1',
                        'code'       => 'title',
                        'label'      => 'Заголовок',
                        'validation' => 'required|string|min:5|max:255',
                    ],
                    [
                        'column'     => '__date_1',
                        'code'       => 'publishedAt',
                        'label'      => 'Дата публикации',
                        'validation' => 'required|date',
                    ],
                    [
                        'column' => '__data',
                        'code'   => 'content',
                        'data'   => [
                            [
                                'type'       => 'text',
                                'code'       => 'body',
                                'label'      => 'Текст',
                                'editor'     => 'html',
                                'validation' => 'required|string|min:20',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // заголовок, сколько дней назад, видимость
        $rows = [
            ['Открылся новый сырный отдел',            1,  true],
            ['Привезли вино нового урожая',            4,  true],
            ['Хлеб теперь пекут дважды в день',        9,  true],
            ['Скидка на молочное по вторникам',       12,  true],
            ['Меняем поставщика бакалеи',             18,  false],
            ['Как выбрать сыр к вину',                25,  true],
            ['Летнее расписание работы',              33,  true],
            ['Сырная дегустация в субботу',           40,  true],
            ['Рассказываем про закваску',             52,  true],
            ['Готовим ассортимент к праздникам',      64,  false],
        ];

        foreach ($rows as [$title, $daysAgo, $visible]) {
            $feed->items()->create([
                'title'       => $title,
                'publishedAt' => Carbon::now()->subDays($daysAgo),
                'body'        => '<p>' . $title . '. Подробности в магазине и на сайте.</p>',
                '__visible'   => $visible,
            ]);
        }

        return count($rows);
    }
}

// php artisan db:seed --class="MagicProDatabase\seeders\FeedDemoSeeder"
