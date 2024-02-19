<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dialer_petty_cash_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('petty_cash_location_id')->nullable();
            $table->foreign('petty_cash_location_id')->references('id')->on('dialer_petty_cash_locations')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('petty_cash_note_id')->nullable();
            $table->foreign('petty_cash_note_id')->references('id')->on('dialer_petty_cash_notes')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('petty_cash_vendor_id')->nullable();
            $table->foreign('petty_cash_vendor_id')->references('id')->on('dialer_petty_cash_vendors')->restrictOnDelete()->restrictOnUpdate();
        });

        // Convert existing "notes" column into a DialerPettyCashNote.
        $entries = \App\Models\DialerPettyCashEntry::query()
            ->get();

        foreach ($entries as $entry) {
            if (!empty($entry->notes)) {
                $note = \App\Models\DialerPettyCashNote::where('note', $entry->notes)->first();
                if (empty($note)) {
                    $note = new \App\Models\DialerPettyCashNote();
                    $note->note = $entry->notes;
                    $note->save();
                }

                $entry->petty_cash_note_id = $note->id;
                $entry->save();
            }
        }

        Schema::table('dialer_petty_cash_entries', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialer_petty_cash_entries', function (Blueprint $table) {
            $table->dropForeign('dialer_petty_cash_entries_petty_cash_location_id_foreign');
            $table->dropColumn('petty_cash_location_id');
            $table->dropForeign('dialer_petty_cash_entries_petty_cash_note_id_foreign');
            $table->dropColumn('petty_cash_note_id');
            $table->dropForeign('dialer_petty_cash_entries_petty_cash_vendor_id_foreign');
            $table->dropColumn('petty_cash_vendor_id');
            $table->text('notes')->nullable()->after('date');
        });
    }
};
