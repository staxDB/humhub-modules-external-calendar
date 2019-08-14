<?php

use humhub\components\Migration;

class m171126_184913_recurrence_until extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'recurrence_until', $this->dateTime());
    }

    public function down()
    {
        echo "m171126_184912_recurrence_id does not support migration down.\n";
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
