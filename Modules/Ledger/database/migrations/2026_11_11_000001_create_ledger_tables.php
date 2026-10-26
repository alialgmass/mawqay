<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            // Empty for Platform-only accounts (cash, Commission revenue).
            $table->foreignId('store_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->string('posting_group');
            $table->string('account');
            $table->string('direction');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('reverses_entry_id')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->string('idempotency_key')->unique();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['store_id', 'account']);
            $table->index(['order_id', 'account']);
            $table->index('posting_group');
            $table->index('reverses_entry_id');
        });

        $this->protectEntries();

        Schema::create('ledger_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->unsignedBigInteger('total_debits');
            $table->unsignedBigInteger('total_credits');
            $table->bigInteger('platform_cash');
            $table->bigInteger('payables');
            $table->bigInteger('commission_revenue');
            $table->json('mismatches')->nullable();
            $table->unsignedBigInteger('ran_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_reconciliations');

        $this->unprotectEntries();

        Schema::dropIfExists('ledger_entries');
    }

    /**
     * Database-level refusal of update and delete where the engine allows it. The SQLite
     * test database relies on the model guard alone.
     */
    private function protectEntries(): void
    {
        $message = 'Ledger entries are append-only.';

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => $this->mysqlTriggers($message),
            'pgsql' => $this->postgresTriggers($message),
            default => null,
        };
    }

    private function unprotectEntries(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => collect(['update', 'delete'])->each(fn (string $verb) => DB::unprepared("DROP TRIGGER IF EXISTS ledger_entries_no_{$verb}")),
            'pgsql' => DB::unprepared('DROP TRIGGER IF EXISTS ledger_entries_append_only ON ledger_entries; DROP FUNCTION IF EXISTS ledger_entries_refuse_change();'),
            default => null,
        };
    }

    private function mysqlTriggers(string $message): void
    {
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $verb => $event) {
            DB::unprepared("CREATE TRIGGER ledger_entries_no_{$verb} BEFORE {$event} ON ledger_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'");
        }
    }

    private function postgresTriggers(string $message): void
    {
        DB::unprepared("CREATE FUNCTION ledger_entries_refuse_change() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION '{$message}'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER ledger_entries_append_only BEFORE UPDATE OR DELETE ON ledger_entries FOR EACH ROW EXECUTE FUNCTION ledger_entries_refuse_change()');
    }
};
