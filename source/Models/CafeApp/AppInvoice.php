<?php

namespace Source\Models\CafeApp;

use Source\Core\Model;

/**
 * Description of AppInvoice
 * @property int $user_id
 * @property int wallet_id
 * @property int category_id
 * @property int|null invoice_of
 * @property string description
 * @property string type
 * @property string value
 * @property string currency
 * @property string due_at
 * @property string repeat_when
 * @property string status
 *
 * @author willr
 */
class AppInvoice extends Model
{

    public function __construct()
    {
        parent::__construct("app_invoices", ['id'], [
            "user_id", "wallet_id", "category_id",
            "description", "type", "value", "currency",
            "due_at", "repeat_when",
        ]);
    }
}
