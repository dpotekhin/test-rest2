<?php

use yii\db\Migration;

/**
 * Class m180606_115603_update_user_table
 */
class m180606_115603_update_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180606_115603_update_user_table cannot be reverted.\n";

        return false;
    }

    public function up()
    {
        $this->addColumn('{{%user}}', 'access_token', $this->string()->unique()->after('auth_key'));
        $this->addColumn('{{%user}}', 'email_confirm_token', $this->string()->unique());
        $this->addColumn('{{%user}}', 'email_confirmed', $this->boolean()->defaultValue(false));
    }

    public function down()
    {
        $this->dropColumn('{{%user}}', 'access_token');
        $this->dropColumn('{{%user}}', 'email_confirm_token');
        $this->dropColumn('{{%user}}', 'email_confirmed');
    }
}
