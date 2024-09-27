<?php

namespace Source\App;

use Source\Core\Controller;
use Source\Core\View;
use Source\Models\Auth;
use Source\Models\CafeApp\AppCategory;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Models\Post;
use Source\Support\Email;
use Source\Support\Message;

/**
 * Class App
 * @package Source\App
 */
class App extends Controller
{

    /** @var ?User */
    private ?User $user;

    /**
     * App constructor.
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if (!$this->user = Auth::user()) {
            $this->message->warning("Efetue login para acessar o APP.")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();
        (new AppInvoice())->fixed($this->user, 3);
    }

    /**
     * APP HOME
     */
    public function home(): void
    {
        $head = $this->seo->render(
            "Olá {$this->user->first_name}. Vamos controlar? - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        // CHART

        $dateChart = [];

        for ($month = -4; $month <= 0; $month++) {
            $dateChart[] = date("m:Y", strtotime("{$month}month"));
        }

        $chartData = new \stdClass();
        $chartData->categories = "'" . implode("','", $dateChart) . "'";
        $chartData->expense = "0,0,0,0,0";
        $chartData->income = "0,0,0,0,0";

        $chart = (new AppInvoice())
            ->find(
                "user_id = :user AND status = :status AND due_at >= DATE(now() - INTERVAL 4 MONTH) GROUP BY year(due_at), month(due_at)",
                "user={$this->user->id}&status=paid",
                "
                         year(due_at) AS due_year, 
                         month(due_at) as due_month,
                         DATE_FORMAT(due_at, '%m/%Y') AS due_date,
                         (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' AND year(due_at) = due_year AND month(due_at) = due_month) AS income,
                         (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' AND year(due_at) = due_year AND month(due_at) = due_month) AS expense
                        "
            )
            ->limit(5)->fetch(true);


        if ($chart) {
            $charCategories = [];
            $charExpense = [];
            $chartIncome = [];

            foreach ($chart as $item) {
                $charCategories[] = $item->due_date;
                $charExpense[] = $item->expense ?? 0;
                $chartIncome[] = $item->income ?? 0;
            }

            $chartData->categories = "'" . implode("','", $charCategories) . "'";
            $chartData->expense = implode(",", array_map("abs", $charExpense));
            $chartData->income = implode(",", array_map("abs", $chartIncome));
        }
        // END CHART
        // INCOME && EXPANSE

        $income = (new AppInvoice())
            ->find(
                "user_id = :user AND type = 'income' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 4 MONTH)",
                "user={$this->user->id}"
            )
            ->order("due_at")
            ->fetch(true);

        $expense = (new AppInvoice())
            ->find(
                "user_id = :user AND type = 'expense' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 1 MONTH)",
                "user={$this->user->id}"
            )
            ->order("due_at")
            ->fetch(true);

        // END INCOME && EXPANSE
        //WALLET

        $wallet = (new AppInvoice())->find(
            "user_id = :user AND status = :status",
            "user={$this->user->id}&status=paid",
            "(SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income')  AS income,
                 (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense') AS expense
                 "
        )->fetch();

        //var_dump($wallat);

        if ($wallet) {
            $wallet->wallet = $wallet->income - $wallet->expense;
        }
        //END WALLET

        $posts = (new Post())->find()->limit(3)->order("post_at DESC")
            ->fetch(true);

        echo $this->view->render("home", [
            "head" => $head,
            "chart" => $chartData,
            "income" => $income,
            "expense" => $expense,
            "wallet" => $wallet,
            "posts" => $posts
        ]);
    }

   
    /**
     * @param array $data
     * @return void
     */
    public function filter(array $data)
    {
        $status = !empty($data['status']) ? $data['status'] : "all";
        $category = !empty($data['category']) ? $data['category'] : "all";
        $date = !empty($data['date']) ? $data['date'] : date("m/Y");

        list($m, $y) = explode("/", $date);
        $m = $m >= 1 && $m <= 12 ? $m : date('m');
        $y = $y <= date(
            "Y",
            strtotime("+10year")
        ) ? $y : date("Y", strtotime("+10year"));

        $start = new \DateTime(date("Y-m-t"));
        $end = new \DateTime(date("Y-m-t", strtotime("{$y}-{$m}+1month")));
        $diff = $start->diff($end);

        if ($diff->invert) {
            $afterMonth = floor($diff->days / 30);
            (new AppInvoice())->fixed($this->user, $afterMonth);
        }

        $redirect = $data['filter'] == "income" ? "receber" : "pagar";
        $json['redirect'] = url("/app/{$redirect}/{$status}/{$category}/{$m}-{$y}");        
        echo json_encode($json);

    }

    /**
     * APP INCOME (Receber)
     * @param array|null $data
     * @return void
     */
    public function income(?array $data): void
    {
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())->find("type = :t", "t=income", "id, name")
            ->order("order_by, name")
            ->fetch(true);        

        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "income",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, 'income', $data ?? null),
            "filter" => (object) [
                "status" => ($data["status"] ?? null),
                "category" => ($data["category"] ?? null),
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
            ]
        ]);
    }


    /**
     * @param array|null $data
     * @return void
     */
    public function expense(?array $data): void
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())->find("type = :t", "t=expense", "id, name")
            ->order("order_by, name")
            ->fetch(true);

        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "expense",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, 'expense', $data ?? null),
            "filter" => (object) [
                "status" => ($data["status"] ?? null),
                "category" => ($data["category"] ?? null),
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
            ]
        ]);
    }

    /**
     * @param array $data
     * @return void
     */
    public function launch(array $data): void
    {
        if (request_limit("applaunch", 20, 60 * 5)) {
            $json['message'] = $this->message->warning("Foi muito rápido {$this->user->first_name}! Por favor aguarde 5 min para novos lançamentos")->render();
            echo json_encode($json);
            return;
        }

        if (!empty($data["enrollments"]) && ($data["enrollments"] < 2) || ($data['enrollments'] > 420)) {
            $json['message'] = $this->message->warning("Ooops! {$this->user->first_name}! Para lançar o número de parcelas deve ser entre 2 e 420.")->render();
            echo json_encode($json);
            return;
        }

        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = (date($data['due_at']) <= date("Y-m-d") ? "paid" : "unpaid");

        $invoice = (new AppInvoice());
        $invoice->user_id = $this->user->id;
        $invoice->wallet_id = $data["wallet"];
        $invoice->category_id = $data["category"];
        $invoice->invoice_of = null;
        $invoice->description = $data['description'];
        $invoice->type = $data["repeat_when"] == "fixed" ? "fixed_{$data['type']}" : $data["type"];
        $invoice->value = str_replace([".", ","], ["", "."], $data['value']);
        $invoice->currency = $data['currency'];
        $invoice->due_at = $data['due_at'];
        $invoice->repeat_when = $data['repeat_when'];
        $invoice->period = (!empty($data['period']) ? $data['period'] : "month");
        $invoice->enrollments = (!empty($data['enrollments']) ? $data['enrollments'] : 1);
        $invoice->status = ($data['repeat_when'] == 'fixed' ? 'paid' : $status);

        if (!$invoice->save()) {
            $json['message'] = $invoice->message()->before("Ooops! ")->render();
            echo json_encode($json);
            return;
        }

        if ($invoice->repeat_when == "enrollment") {
            $invoiceOf = $invoice->id;
            for ($enrollment = 1; $enrollment < $invoice->enrollments; $enrollment++) {
                $invoice->id = null;
                $invoice->invoice_of = $invoiceOf;
                $invoice->due_at = date('Y-m-d', strtotime($data['due_at'] . "+{$enrollment}month"));
                $invoice->status = (date($invoice->due_at) <= date("Y-m-d") ? "paid" : "unpaid");
                $invoice->enrollment_of = $enrollment + 1;

                if (!$invoice->save()) {
                    $json['message'] = $invoice->message()->before("Ooops! ")->render();
                    echo json_encode($json);
                    return;
                }

            }
        }

        if ($invoice->type == 'income') {
            $this->message->success("Receita lançada com sucesso. Use o filtro para controlar.")->render();
        } else {
            $this->message->success("Despesa lançada com sucesso. Use o filtro para controlar.")->render();
        }

        $json['reload'] = true;
        echo json_encode($json);
    }

    /**
     * @param array $data
     * @return void
     */
    public function support(array $data): void
    {
        if (empty($data['message'])) {
            $json["message"] = $this->message->warning("Para enviar escreva sua mensagem.")->render();
            echo json_encode($json);
            return;
        }

        if (request_limit("appsupport", 3, 5 * 60)) {
            $json['message'] = $this->message->warning("Por favor, aguarde 5 minutos para enviar novos contatos, sugestões ou reclamações")
                ->render();
            echo json_encode($json);
            return;
        }

        if (request_repeat("message", $data["message"])) {
            $json['message'] = $this->message->info("Já recebemos sua solicitação {$this->user->first_name}. Agredecemos o seu contato")
                ->render();
            echo json_encode($json);
            return;
        }

        $subject = date_fmt() . " - {$data['subject']}";
        $message = filter_var($data['message'], FILTER_SANITIZE_SPECIAL_CHARS);

        $view = new View(__DIR__ . "/../../shared/views/email/");

        $body = $view->render("mail", [
            "subject" => $subject,
            "message" => str_textarea($message)
        ]);

        (new Email())->bootstrap(
            $subject,
            $body,
            CONF_MAIL_SUPPORT,
            "Suporte " . CONF_SITE_NAME
        )->queue($this->user->email, "{$this->user->first_name} {$this->user->last_name}");

        $this->message->success("Recebemos sua solicitação {$this->user->first_name}. Agradecemos pelo contato e responderemos em breve")->flash();
        $json['reload'] = true;
        echo json_encode($json);

    }

    /**
     * APLICATIVO INVOICE (Fatura)
     */
    public function invoice(): void
    {
        $head = $this->seo->render(
            "Aluguel - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("invoice", [
            "head" => $head
        ]);
    }

    /**
     * APP PROFILE (Perfil)
     */
    public function profile(): void
    {
        $head = $this->seo->render(
            "Meu perfil - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("profile", [
            "head" => $head
        ]);
    }

    /**
     * APP LOGOUT
     */
    public function logout(): void
    {
        (new Message())->info("Você saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}
