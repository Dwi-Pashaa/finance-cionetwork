<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateUserManualPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PDF Panduan Penggunaan Aplikasi CIO Finance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Membuat dokumen PDF Panduan Penggunaan Aplikasi...');

        $pdf = Pdf::loadView('exports.user_manual_pdf');
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $docsPath = base_path('docs');
        if (!File::exists($docsPath)) {
            File::makeDirectory($docsPath, 0755, true);
        }

        $publicDocsPath = public_path('docs');
        if (!File::exists($publicDocsPath)) {
            File::makeDirectory($publicDocsPath, 0755, true);
        }

        $filePath1 = $docsPath . '/PANDUAN_PENGGUNAAN_APLIKASI.pdf';
        $filePath2 = $publicDocsPath . '/PANDUAN_PENGGUNAAN_APLIKASI.pdf';

        $output = $pdf->output();

        File::put($filePath1, $output);
        File::put($filePath2, $output);

        $this->info("Berhasil membuat PDF di:\n- {$filePath1}\n- {$filePath2}");

        return Command::SUCCESS;
    }
}
