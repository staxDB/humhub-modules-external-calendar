<?php

use humhub\components\Migration;

class m171126_184912_recurrence_id extends Migration
{
    public function up()
    {
        $tableSchema = $this->db->getTableSchema('external_calendar_entry', true);

        // If the table does not exists, we want the default exception behavior
        if(!in_array('recurrence_id', $tableSchema->columnNames, true)) {
            $this->addColumn('external_calendar_entry', 'recurrence_id', $this->string());
        }
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
