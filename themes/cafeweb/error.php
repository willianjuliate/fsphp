<?php /** @var \League\Plates\Template\Template $this */
$this->layout("_theme", ["head" => $head]); ?>

<article class="not_found">
    <div class="container content">
        <header class="not_found_header">
            <p class="error">&bull;<?= $error->code; ?>&bull;</p>
            <h1><?= $error->title; ?></h1>
            <p><?= $error->message; ?></p>
            <?php if ($error->link): ?>
                <a class="not_found_btn gradient gradient-green gradient-hover transition radius"
                    title="<?= $error->linkTitle; ?>" href="<?= $error->link; ?>"><?= $error->linkTitle; ?></a>
            <?php endif; ?>
        </header>
    </div>
</article>