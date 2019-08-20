<?php

use humhub\components\Migration;

class m171126_184912_recurrence_id extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'recurrence_id', $this->string());
        $this->createIndex('idx_unique_external-calendar_entry_recurrence', 'external_calendar_entry', ['parent_event_id', 'recurrence_id']);
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
