<?php


namespace models\billing;


use models\Model;

class Admin extends Model
{
    /**
     *
     */
    const FULL_ADMINISTRATOR_ROLE_ID = 1;

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tbladmins';

    /**
     * @return self
     * @throws \Exception
     */
    public function getDefault()
    {
        $data = self::where('roleid', self::FULL_ADMINISTRATOR_ROLE_ID)->where('disabled', 0)->first();

        if (!$data) {
            throw new \Exception('Can\'t get admin user.');
        }

        return $data;
    }

    /**
     * @return self
     */
    public static function getCurrent()
    {
        if (isset($_SESSION['adminid']) && $_SESSION['adminid']) {
            return self::find($_SESSION['adminid']);
        } else {
            return self::getDefault();
        }
    }
}