<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['store_id', 'action']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        $message = 'Audit entries are append-only.';

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => $this->mysqlTriggers($message),
            'pgsql' => $this->postgresTriggers($message),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => collect(['update', 'delete'])->each(fn (string $verb) => DB::unprepared("DROP TRIGGER IF EXISTS audit_entries_no_{$verb}")),
            'pgsql' => DB::unprepared('DROP TRIGGER IF EXISTS audit_entries_append_only ON audit_entries; DROP FUNCTION IF EXISTS audit_entries_refuse_change();'),
            default => null,
        };

        Schema::dropIfExists('audit_entries');
    }

    private function mysqlTriggers(string $message): void
    {
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $verb => $event) {
            DB::unprepared("CREATE TRIGGER audit_entries_no_{$verb} BEFORE {$event} ON audit_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'");
        }
    }

    private function postgresTriggers(string $message): void
    {
        DB::unprepared("CREATE FUNCTION audit_entries_refuse_change() RETURNS trigger AS \$\$ BEGIN RAISE EXCEPTION '{$message}'; END; \$\$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER audit_entries_append_only BEFORE UPDATE OR DELETE ON audit_entries FOR EACH ROW EXECUTE FUNCTION audit_entries_refuse_change()');
    }
};
