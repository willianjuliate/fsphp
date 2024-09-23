<?php

namespace Source\App;

use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Models\Post;
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
     * APP INCOME (Receber)
     */
    public function income(): void
    {
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("income", [
            "head" => $head
        ]);
    }

    /**
     * APP EXPENSE (Pagar)
     */
    public function expense(): void
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("expense", [
            "head" => $head
        ]);
    }

    public function launch(array $data): void
    {
        if (request_limit("applaunch", 20, 60 * 5)) {
            $json['message'] = $this->message->warning("Foi muito rápido {$this->user->first_name}! Por favor aguarde 5 min para novos lançamentos")->render();
            echo json_encode($json);
            return;
        }

        if (!empty($data["enrollments"]) && ($data["enrollments"] < 2) || ($data['enrollments'] > 420) ) {
            $json['message'] = $this->message->warning("Ooops! {$this->user->first_name}! Para lançar o número de parcelas deve ser entre 2 e 420.")->render();
            echo json_encode($json);
            return;
        }

        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = (date($data['due_at']) <= date("Y-m-d") ? "paid": "unpaid");

        $invoice = (new AppInvoice());
        $invoice->user_id = $this->user->id;
        $invoice->wallet_id = $data["wallet"];
        $invoice->category_id = $data["category"];
        $invoice->invoice_of = null;
        $invoice->description = $data['description'];
        $invoice->type = $data["repeat_when"] == "fixed" ? "fixed_{$data['type']}" : $data["type"];
        $invoice->value = str_replace(',', '.', $data['value']);

        $json['data'] = $data;
        $json['$invoice'] = $invoice->data();
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
