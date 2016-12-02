<?php


namespace oog\uitzendingen;


class Archive
{
    public function __construct()
    {
        add_action('pre_get_posts', [$this, 'preGetPosts']);
    }

    public function preGetPosts(\WP_Query $query)
    {
        if (is_archive() &&
            ($query->is_post_type_archive(Uitzending::POST_TYPE_TV) ||
                $query->is_post_type_archive(Uitzending::POST_TYPE_RADIO))
        ) {
            $query->query_vars = array_merge($query->query_vars , $this->filterArchive($query));
        }

        return $query;
    }

    public function filterArchive()
    {
        $queryVars = [];

        // We're filtering our posts
        $data = filter_input_array(INPUT_POST, [
            'programme' => FILTER_VALIDATE_INT,
            'datefrom' => FILTER_SANITIZE_STRING,
            'dateto' => FILTER_SANITIZE_STRING,
            'keyword' => FILTER_SANITIZE_STRING,
        ]);

        if ($data['programme']) {
                $queryVars['tax_query'] = [[
                'taxonomy' => Uitzending::TAXONOMY_PROGRAMME,
                'field' => 'id',
                'terms' => $data['programme']
            ]];
        }

        if ($data['keyword'] && $data['keyword'] !== '') {
            $queryVars['s'] = $data['keyword'];
        }

        if ($data['datefrom'] || $data['dateto']) {
            $dateFrom = [
                'year' => 0,
                'month' => 1,
                'day' => 1
            ];
            $dateTo = [
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d')
            ];

            if ($data['datefrom']) {
                $date = explode('-', $data['datefrom']);
                if (count($date) === 3) {
                    $dateFrom['year'] = $date[2] * 1;
                    $dateFrom['month'] = $date[1] * 1;
                    $dateFrom['day'] = $date[0] * 1;
                }
            }

            if ($data['dateto']) {
                $date = explode('-', $data['dateto']);
                if (count($date) === 3) {
                    $dateTo['year'] = $date[2] * 1;
                    $dateTo['month'] = $date[1] * 1;
                    $dateTo['day'] = $date[0] * 1;
                }
            }

            $queryVars['date_query'] = [[
                'relation' => 'AND',
                [
                    'after' => $dateFrom,
                    'inclusive' => true
                ],
                [
                    'before' => $dateTo,
                    'inclusive' => true
                ]
            ]];
        }

        return $queryVars;

    }
}