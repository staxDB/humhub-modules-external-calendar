<?php

use humhub\components\Migration;

class m171126_184911_add_rrule extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'rrule', $this->string());
    }

    public function down()
    {
        echo "m171126_184911_add_rrule does not support migration down.\n";
        return false;
    }

    /*
      // Use safeUp/safeDown to do migration with transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}
