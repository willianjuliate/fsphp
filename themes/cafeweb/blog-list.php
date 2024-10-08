<article class="blog_article">
    <?php if (!empty($post)): ?>
        <a title="<?= $post->title; ?>" href="<?= url("/blog/{$post->uri}"); ?>">
            <img title="<?= $post->title; ?>" alt="<?= $post->title; ?>"
                 src="<?= image($post->cover, 600, 340); ?>"/>
        </a>
        <header>
            <p class="meta">
                <a title="Artigos em <?= $post->category()->title ?>" href=" <?= url("/blog/em/{$post->category()->uri}") ?>">
                    <?= $post->category()->title ?>
                </a>
                 &bull; Por <a title="Criado por <?= $post->author()->first_name ?>" href="">
                    <?= "{$post->author()->first_name} {$post->author()->last_name}" ?>                
                 </a> 
                 &bull; <?= $post->post_at ?>
                </p>
            <h2><a title="<?= $post->title; ?>" href="<?= url("/blog/{$post->uri}"); ?>"><?= $post->title; ?></a></h2>
            <p><a title="<?= $post->title; ?>" href="<?= url("/blog/{$post->uri}"); ?>"></a><?= str_limit_chars($post->subtitle, 120); ?></p>
        </header>
    <?php endif; ?>
</article>