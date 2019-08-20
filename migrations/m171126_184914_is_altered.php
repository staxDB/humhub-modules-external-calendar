<?php

use humhub\components\Migration;

class m171126_184914_is_altered extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'is_altered', $this->boolean()->defaultValue(0));
    }

    public function down()
    {
        echo "m171126_184914_is_altered does not support migration down.\n";
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
