<?php

if (strpos(url(), "fsphp.me")) {
    /*
     * CSS
     */
    $min_css = new MatthiasMullie\Minify\CSS();
    $min_css->add(__DIR__ . "/../../../shared/styles/styles.css");
    $min_css->add(__DIR__ . "/../../../shared/styles/boot.css");

    // Theme CSS
    $css_dir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css");
    foreach ($css_dir as $css) {
        $css_file = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css/{$css}";
        if (is_file($css_file) && pathinfo($css_file)['extension'] == "css") {
            $min_css->add($css_file);
        }
    }

    // Minify CSS
    $min_css->minify(__DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/style.css");

    /*
     * JS
     */
    $min_js = new MatthiasMullie\Minify\JS();
    $min_js->add(__DIR__ . "/../../../shared/scripts/jquery.min.js");
    $min_js->add(__DIR__ . "/../../../shared/scripts/jquery.form.js");
    $min_js->add(__DIR__ . "/../../../shared/scripts/jquery-ui.js");
    $min_js->add(__DIR__ . "/../../../shared/scripts/jquery.mask.js");
    $min_js->add(__DIR__ . "/../../../shared/scripts/highcharts.js");
    $min_js->add(__DIR__ . "/../../../shared/scripts/tracker.js");

    // Theme JS
    $js_dir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js");
    foreach ($js_dir as $js) {
        $js_file = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js/{$js}";
        if (is_file($js_file) && pathinfo($js_file)['extension'] == "js") {
           $min_js->add($js_file);
        }
    }

    // Minify JS
    $min_js->minify(__DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/scripts.js");

}