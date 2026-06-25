<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // -- Issue 2 : relier les codes promo aux commandes via une vraie cle etrangere --
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('promo_code_id')->nullable()->index()->after('promo_code');
        });

        // Backfill : retrouve l'id du code promo depuis le code texte deja stocke
        DB::statement(
            'UPDATE orders SET promo_code_id = ('
            .'SELECT id FROM promo_codes WHERE promo_codes.code = orders.promo_code'
            .') WHERE promo_code IS NOT NULL'
        );

        // -- Issue 5 : product_subscriptions.order_id / product_id deviennent nullables --
        // (coherent avec user_id deja nullable + suppression compte RGPD)
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE product_subscriptions ALTER COLUMN order_id DROP NOT NULL');
            DB::statement('ALTER TABLE product_subscriptions ALTER COLUMN product_id DROP NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_subscriptions MODIFY order_id INT NULL');
            DB::statement('ALTER TABLE product_subscriptions MODIFY product_id INT NULL');
        }

        // -- Nettoyage des references orphelines avant de poser les contraintes --
        $this->nullifyOrphans('product_subscriptions', 'user_id', 'utilisateurs');
        $this->nullifyOrphans('product_subscriptions', 'order_id', 'orders');
        $this->nullifyOrphans('product_subscriptions', 'product_id', 'products');
        $this->nullifyOrphans('contact_messages', 'user_id', 'utilisateurs');
        $this->nullifyOrphans('chat_logs', 'user_id', 'utilisateurs');
        $this->deleteOrphans('user_addresses', 'user_id', 'utilisateurs');
        $this->deleteOrphans('contact_message_replies', 'contact_message_id', 'contact_messages');

        // -- Issue 2 : FK orders.promo_code_id --
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('promo_code_id', 'fk_order_promo')
                ->references('id')->on('promo_codes')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        // -- Issues 3 & 5 : FK product_subscriptions --
        Schema::table('product_subscriptions', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_psub_user')
                ->references('id')->on('utilisateurs')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('order_id', 'fk_psub_order')
                ->references('id')->on('orders')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('product_id', 'fk_psub_product')
                ->references('id')->on('products')->nullOnDelete()->cascadeOnUpdate();
        });

        // -- Issue 3 : FK user_addresses.user_id (cascade : adresses inutiles sans compte) --
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_address_user')
                ->references('id')->on('utilisateurs')->cascadeOnDelete()->cascadeOnUpdate();
        });

        // -- Issue 8 : FK contact_messages.user_id (nullOnDelete : on garde le message anonymise) --
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_contact_user')
                ->references('id')->on('utilisateurs')->nullOnDelete()->cascadeOnUpdate();
        });

        // -- Issue 3 : FK chat_logs.user_id --
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_chatlog_user')
                ->references('id')->on('utilisateurs')->nullOnDelete()->cascadeOnUpdate();
        });

        // -- Issue 3 : FK contact_message_replies.contact_message_id (cascade) --
        Schema::table('contact_message_replies', function (Blueprint $table) {
            $table->foreign('contact_message_id', 'fk_reply_message')
                ->references('id')->on('contact_messages')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('contact_message_replies', fn (Blueprint $t) => $t->dropForeign('fk_reply_message'));
        Schema::table('chat_logs', fn (Blueprint $t) => $t->dropForeign('fk_chatlog_user'));
        Schema::table('contact_messages', fn (Blueprint $t) => $t->dropForeign('fk_contact_user'));
        Schema::table('user_addresses', fn (Blueprint $t) => $t->dropForeign('fk_address_user'));

        Schema::table('product_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_psub_product');
            $table->dropForeign('fk_psub_order');
            $table->dropForeign('fk_psub_user');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_order_promo');
            $table->dropColumn('promo_code_id');
        });

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE product_subscriptions ALTER COLUMN order_id SET NOT NULL');
            DB::statement('ALTER TABLE product_subscriptions ALTER COLUMN product_id SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_subscriptions MODIFY order_id INT NOT NULL');
            DB::statement('ALTER TABLE product_subscriptions MODIFY product_id INT NOT NULL');
        }
    }

    private function nullifyOrphans(string $table, string $column, string $reference): void
    {
        DB::statement(
            "UPDATE {$table} SET {$column} = NULL "
            ."WHERE {$column} IS NOT NULL "
            ."AND {$column} NOT IN (SELECT id FROM {$reference})"
        );
    }

    private function deleteOrphans(string $table, string $column, string $reference): void
    {
        DB::statement(
            "DELETE FROM {$table} "
            ."WHERE {$column} IS NOT NULL "
            ."AND {$column} NOT IN (SELECT id FROM {$reference})"
        );
    }
};
