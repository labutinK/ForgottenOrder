<?php
namespace Local\HlBlockUtils;

class ForgottenOrder extends BaseHl
{
    public $tableName = 'b_hlbd_forgotten_order';
    public $entity;

    public function __construct()
    {
        parent::__construct();
    }

}