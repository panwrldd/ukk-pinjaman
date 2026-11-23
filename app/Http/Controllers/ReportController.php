<?php

namespace App\Http\Controllers;

use App\Exports\TabularReportExport;
use App\Models\Category;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $availableReports = ReportService::availableReports($user);

        $type = $request->string('type', 'loans')->toString();
        if (!array_key_exists($type, $availableReports)) {
            if (array_key_exists($type, ReportService::availableReports())) {
                abort(403, 'Anda tidak memiliki hak akses untuk jenis laporan tersebut.');
            }
            $type = array_key_first($availableReports) ?? 'loans';
        }

        $filters = $this->extractFilters($request);
        $report = ReportService::buildReport($type, $filters);
        $categories = Category::orderBy('name')->get();
        $institution = $this->institutionMeta();

        return view('reports.index', compact(
            'availableReports',
            'type',
            'filters',
            'report',
            'categories',
            'institution'
        ));
    }

    public function print(Request $request): View
    {
        $type = $this->authorizeAndResolveType($request);
        $filters = $this->extractFilters($request);
        $report = ReportService::buildReport($type, $filters);
        $institution = $this->institutionMeta();

        ActivityLogService::log(
            action: 'report_printed',
            description: "Mencetak {$report['meta']['title']} (tampilan cetak).",
            module: 'reports'
        );

        return view('reports.print', compact('type', 'filters', 'report', 'institution'));
    }

    public function exportPdf(Request $request): Response
    {
        $type = $this->authorizeAndResolveType($request);
        $filters = $this->extractFilters($request);
        $report = ReportService::buildReport($type, $filters);
        $institution = $this->institutionMeta();

        ActivityLogService::log(
            action: 'report_exported_pdf',
            description: "Mengunduh PDF {$report['meta']['title']}.",
            module: 'reports'
        );

        $pdf = Pdf::loadView('reports.pdf', compact('type', 'filters', 'report', 'institution'))
            ->setPaper('a4', in_array($type, ['loans', 'returns', 'equipments', 'fines'], true) ? 'landscape' : 'portrait');

        $filename = 'GearHub_' . strtoupper($type) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $type = $this->authorizeAndResolveType($request);
        $filters = $this->extractFilters($request);
        $report = ReportService::buildReport($type, $filters);

        ActivityLogService::log(
            action: 'report_exported_excel',
            description: "Mengekspor Excel {$report['meta']['title']}.",
            module: 'reports'
        );

        $filename = 'GearHub_' . strtoupper($type) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TabularReportExport(
                $report['meta']['title'],
                $report['headings'],
                $report['rows']
            ),
            $filename
        );
    }

    private function authorizeAndResolveType(Request $request): string
    {
        $user = Auth::user();
        $availableReports = ReportService::availableReports($user);
        $type = $request->string('type', 'loans')->toString();

        if (!array_key_exists($type, $availableReports)) {
            abort(403, 'Anda tidak memiliki hak akses untuk jenis laporan tersebut.');
        }

        return $type;
    }

    private function extractFilters(Request $request): array
    {
        return [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'status' => $request->string('status')->toString(),
            'category_id' => $request->string('category_id')->toString(),
            'condition' => $request->string('condition')->toString(),
            'role' => $request->string('role')->toString(),
        ];
    }

    private function institutionMeta(): array
    {
        return [
            'name' => Setting::get('institution_name', 'SMK Negeri 1 Rekayasa & Multimedia'),
            'unit' => Setting::get('institution_unit', 'Unit Pelaksana Teknis Laboratorium RPL & Multimedia'),
            'address' => Setting::get('institution_address', 'Jl. Pendidikan Teknologi No. 45'),
            'phone' => Setting::get('institution_phone', '(021) 5589-2410'),
        ];
    }
}
