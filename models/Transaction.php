<?php namespace Shohabbos\Paymeshopaholic\Models;

use Model;

/**
 * Model
 */
class Transaction extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'shohabbos_paymeshopaholic_transactions';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
