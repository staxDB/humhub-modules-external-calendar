<?php

use humhub\components\Migration;

class m171126_184912_add_parent_event extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'parent_event_id', $this->integer()->null());
        $this->addForeignKey('fk-external-calendar-parent-entry','external_calendar_entry','parent_event_id', 'external_calendar_entry', 'id');
    }

    public function down()
    {
        echo "m171126_184912_add_parent_event does not support migration down.\n";
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
