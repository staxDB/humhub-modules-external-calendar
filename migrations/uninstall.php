<?php

use yii\db\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->dropTable('external_calendar_entry');
        $this->dropTable('external_calendar');
        $this->dropTable('external_calendar_export_spaces');
        $this->dropTable('external_calendar_export');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}
