<?php
/**
 * WoodCon - Tin tức
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\News;

class NewsController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $data = News::paginated($page, 9);
        $this->render('news_list', [
            'pageTitle' => 'Tin tức - WoodCon',
            'posts'     => $data['rows'],
            'pager'     => $data['pager'],
            'total'     => $data['total'],
        ]);
    }

    public function show(string $slug): void
    {
        $post = News::bySlug($slug);
        if (!$post) {
            http_response_code(404);
            require_once BASE_PATH . '/web/views/404.php';
            exit;
        }
        $related = News::latest(3);
        $this->render('news_detail', [
            'pageTitle' => $post['title'] . ' - WoodCon',
            'post'      => $post,
            'related'   => $related,
        ]);
    }
}