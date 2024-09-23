<?php

namespace Source\App;

use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Category;
use Source\Models\Faq\Question;
use Source\Models\Post;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Pager;

/**
 * CONTROLLER WEB
 * @package Source\App
 */
class Web extends Controller
{

    /**
     * CONSTRUCT WEB
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");
        (new Access())->report();
        (new Online())->report();
    }

    /**
     * SITE HOME
     * @return void
     */
    public function home(): void
    {
        $head = $this->seo->render(
                CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url(),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("home", [
            "head" => $head,
            "video" => "jzvREL0hOvE?si=CuQx2qjGgZ4kB3A7",
            "blog" => (new Post())
                    ->find()
                    ->order("post_at DESC")
                    ->limit(6)
                    ->fetch(true)
        ]);
    }

    /**
     * SITE ABOUT
     * @return void
     */
    public function about(): void
    {
        $head = $this->seo->render(
                "Descubra o " . CONF_SITE_NAME . " - " . CONF_SITE_DESC,
                CONF_SITE_DESC,
                url("/sobre"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("about", [
            'head' => $head,
            "video" => "jzvREL0hOvE?si=CuQx2qjGgZ4kB3A7",
            "faq" => (new Question())
                    ->find("channel_id = :id", "id=1", "question, response")
                    ->order("order_by")
                    ->fetch(true)
        ]);
    }

    /**
     * SITE BLOG
     * @param array|null $data
     * @return void
     */
    public function blog(?array $data): void
    {
        $head = $this->seo->render(
                "Blog -  " . CONF_SITE_NAME,
                "Confira em nosso blog dicas e sacadas de como controlar melhor suas contas. Vamos tomar um café?",
                url("/blog"),
                theme("/assets/imagens/share.jpg")
        );

        $blog = (new Post())->find();
        $pager = new Pager(url("/blog/p/"));
        $pager->pager($blog->count(), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            'head' => $head,
            'blog' => $blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            'paginator' => $pager->render()
        ]);
    }

    /**
     * Summary of blogCategory
     * @param array $data
     * @return void
     */
    public function blogCategory(array $data): void
    {
        $categoryUri = filter_var($data['category'], FILTER_SANITIZE_SPECIAL_CHARS);
        $category = (new Category())->findByUri($categoryUri);

        if (!$category) {
            redirect("/blog");
        }

        $blogCategory = (new Post())->find("category = :c", "c={$category->id}");
        $page = !empty($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1;
        $pager = new Pager(url("/blog/em/{$category->uri}/"));
        $pager->pager($blogCategory->count(), 9, $page);

        $head = $this->seo->render(
                "Artigos em {$category->title} - " . CONF_SITE_NAME,
                $category->description,
                url("/blog/em/$category->uri/{$page}"),
                $category->cover ? image($category->cover, 1200, 628) : theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Artigos em {$category->title}",
            "desc" => $category->description,
            "blog" => $blogCategory
                    ->limit($pager->limit())
                    ->offset($pager->offset())
                    ->order("post_at DESC")
                    ->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * SITE BLOG SEARCH
     * @param array $data
     * @return void
     */
    public function blogSearch(array $data): void
    {
        if (!empty($data['s'])) {
            $search = filter_var($data['s'], FILTER_SANITIZE_SPECIAL_CHARS);
            echo json_encode(['redirect' => url("/blog/buscar/{$search}/1")]);
            return;
        }

        if (empty($data['terms'])) {
            redirect("/blog");
        }

        $search = filter_var($data['terms'], FILTER_SANITIZE_SPECIAL_CHARS);
        $page = filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1;

        $head = $this->seo->render(
                "Pesquisa por {$search} - " . CONF_SITE_NAME,
                "Confira os resultados de sua pesquisa para {$search}",
                url("/blog/buscar/{$search}/{$page}"),
                theme("/assets/images/share.jpg")
        );

        $blogSearch = (new Post())
                ->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");

        if (!$blogSearch->count()) {
            echo $this->view->render("blog", [
                "head" => $head,
                "title" => "PESQUISA POR:",
                "search" => $search
            ]);
            return;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager($blogSearch->count(), 9, $page);

        echo $this->view->render(
                "blog",
                [
                    "head" => $head,
                    "title" => "PESQUISA POR:",
                    "search" => $search,
                    "blog" => $blogSearch
                            ->limit($pager->limit())
                            ->offset($pager->offset())
                            ->fetch(true),
                    "paginator" => $pager->render()
                ]
        );
    }

    /**
     * SITE BLOG POST
     * @param array $data
     * @return void
     */
    public function blogPost(array $data): void
    {
        $post = (new Post())->findByUri($data['uri']);

        if (!$post) {
            redirect("/404");
        }

        $post->views += 1;
        $post->save();

        $head = $this->seo->render(
                "{$post->title} -  " . CONF_SITE_NAME,
                "{$post->subtitle}",
                url("/blog/{$post->uri}"),
                image($post->cover, 1200, 628)
        );

        echo $this->view->render("blog-post", [
            'head' => $head,
            'post' => $post,
            'related' => (new Post())
                    ->find("category = :c AND id != :i", "c={$post->category}&i={$post->id}")
                    ->order("rand()")
                    ->limit(3)
                    ->fetch(true)
        ]);
    }

    /**
     * SITE LOGIN
     * @param null|array $data
     * @return void
     */
    public function login(?array $data): void
    {
        if (Auth::user()) {
            redirect("/app");
        }
        
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (request_limit("web_login", 3, 60 * 5)) {
                $json['message'] = $this->message->error("Você já efetuou 3 tentativas, esse é o limite. Por favor aguarde por 5 min")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data["email"]) || empty($data["password"])) {
                $json["message"] = $this->message->warning("Informe seu email e senha para entrar");
                echo json_encode($json);
                return;
            }

            $save = !empty($data['save']);
            $auth = new Auth();
            $login = $auth->login($data['email'], $data['password'], $save);

            if ($login) {
                $json['redirect'] = url("/app");
            } else {
                $json['message'] = $auth->message()->before("Oops! ")->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
                "Entrar - " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url("/entrar"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("auth-login", [
            "head" => $head,
            "cookie" => filter_input(INPUT_COOKIE, "authEmail")
        ]);
    }

    /**
     * SITE FORGET
     * @param array|null $data
     * @return void
     */
    public function forget(?array $data): void
    {
        if (Auth::user()) {
            redirect("/app");
        }
        
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['email'])) {
                $json['message'] = $this->message->info("Informe o E-mail para continuar")->render();
                echo json_encode($json);
                return;
            }

            if (request_repeat("web_forget", $data['email'])) {
                $json['message'] = $this->message->error("Oops! Você já tentou este email antes")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            if ($auth->forget($data['email'])) {
                $json['message'] = $this->message->success("Acesse seu email para recuperar a senha")->render();
            } else {
                $json['message'] = $auth->message()->before("Oops! ")->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
                "Recuperar Senha - " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url("/recuperar"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("auth-forget", [
            "head" => $head,
        ]);
    }

    /**
     * SITE RESET PASSWORD
     * @param array $data
     * @return void
     */
    public function reset(array $data): void
    {
        if (Auth::user()) {
            redirect("/app");
        }
        
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['password']) || empty($data['password_re'])) {
                $json['message'] = $this->message->info("Informe e repita a senha para continuar")->render();
                echo json_encode($json);
                return;
            }

            [$email, $code] = explode('|', $data['code']);

            $auth = new Auth();

            if ($auth->reset($email, $code, $data['password'], $data['password_re'])) {
                $this->message->success("Senha alterada com sucesso. Vamos controlar?")->flash();
                $json['redirect'] = url('/entrar');
            } else {
                $json['message'] = $auth->message()->before("Oops! ")->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
                "Crie sua nova senha no " . CONF_SITE_NAME,
                CONF_SITE_DESC,
                url("/recuperar"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("auth-reset", [
            "head" => $head,
            "code" => $data['code'],
        ]);
    }

    /**
     * SITE REGISTER
     * @param null|array $data
     * @return void
     */
    public function register(?array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (in_array("", $data)) {
                $json['message'] = $this->message->info("Informe seus dados para criar sua conta.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $user = new User();

            $user->bootstrap(
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $data['password']
            );

            if ($auth->register($user)) {
                $json['redirect'] = url("/confirma");
            } else {
                $json['message'] = $auth->message()->before("Oops! ")->render();
            }
            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
                "Criar conta - " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url("/cadastrar"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("auth-register", [
            "head" => $head,
        ]);
    }

    /**
     * SITE CONFIRME
     * @return void
     */
    public function confirm(): void
    {
        $head = $this->seo->render(
                "Confirme seu Cadastro - " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url("/confirma"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("optin", [
            "head" => $head,
            "data" => (object) [
                "title" => "Falta pouco! Confirme seu cadastro.",
                "desc" => "Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro e comece a controlar com o CaféControl",
                "image" => theme("/assets/images/optin-confirm.jpg")
            ]
        ]);
    }

    /**
     * SITE SUCCESS
     * @param array $data
     * @return void
     */
    public function success(array $data): void
    {
        $email = base64_decode($data["email"]);
        $user = (new User())->findByEmail($email);

        if ($user && $user->status != "confirmed") {
            $user->status = "confirmed";
            $user->save();
        }

        $head = $this->seo->render(
                "Bem vindo(a) ao " . CONF_SITE_TITLE,
                CONF_SITE_DESC,
                url("/obrigado"),
                theme("/assets/imagens/share.jpg") // trocar img
        );

        echo $this->view->render("optin", [
            "head" => $head,
            "data" => (object) [
                "title" => "Tudo pronto. Você já pode controlar :)",
                "desc" => "Bem-vindo(a) ao seu controle de contas, vamos tomar um café?",
                "image" => theme("/assets/images/optin-success.jpg"),
                "link" => url("/entrar"),
                "linkTitle" => "Fazer Login"
            ],
            "track" => (object) [
                "fb" => "Lead",
                "aw" => "",
            ]
        ]);
    }

    /**
     * SITE TERMS
     * @return void
     */
    public function terms(): void
    {
        $head = $this->seo->render(
                CONF_SITE_NAME . " - Termos de uso",
                CONF_SITE_DESC,
                url("/termos"),
                theme("/assets/imagens/share.jpg")
        );

        echo $this->view->render("terms", [
            'head' => $head,
        ]);
    }

    /**
     * SITE ERROR
     * @param array $data
     * @return void
     */
    public function error(array $data): void
    {
        $error = new \stdClass();

        switch ($data['err_code']) {
            case "problemas":
                $error->code = "OPS";
                $error->title = "Estamos enfrentando problemas!";
                $error->message = "Parece que nosso serviço não está disponível no momento. Já estamos vendo isso mas caso precise, envie um e-mail :)";
                $error->linkTitle = "ENVIAR E-MAIL";
                $error->link = "mailto:" . CONF_MAIL_SUPPORT;
                break;
            case "manutencao":
                $error->code = "OPS";
                $error->title = "Desculpe. Estamos em manutenção!";
                $error->message = "Voltamos logo! Por hora estamos trabalhando para melhorar nosso conteúdo para você controlar as suas contas :P";
                $error->linkTitle = null;
                $error->link = null;
                break;
            default:
                $error->code = $data["err_code"];
                $error->title = "Oops. Conteúdo indisponível :/";
                $error->message = "Sentimos muito, mas o conteúdo qe você tentou acessar não existe, está indisponível no momento ou foi removido :/";
                $error->linkTitle = "Continue navegando";
                $error->link = url_back();
                break;
        }

        $head = $this->seo->render(
                "{$error->code} | {$error->title}",
                $error->message,
                url("/oops/{$error->code}"),
                theme("/assets/imagens/share.jpg"),
                false
        );

        echo $this->view->render("error", [
            "head" => $head,
            "error" => $error
        ]);
    }
}
