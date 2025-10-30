<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ServersImportTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * 返回模板数据
     *
     * @return array
     */
    public function array(): array
    {
        return [
            ['示例服务器1', '生产环境', '192.168.1.100', '22', 'admin', 'password123', 'true'],
            ['示例服务器2', '测试环境', '192.168.1.101', '22', 'root', 'password456', 'true'],
            ['示例服务器3', '开发环境', '192.168.1.102', '2222', 'developer', 'password789', 'false']
        ];
    }

    /**
     * 设置表头
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            '服务器名称（必填）',
            '服务器分组（选填，不存在则自动创建）',
            'IP地址（必填）',
            '端口（选填，默认22）',
            '用户名（必填）',
            '密码（必填）',
            '是否验证连接（选填，默认true）'
        ];
    }

    /**
     * 设置样式
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // 设置表头样式
        $sheet->getStyle('1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // 设置列宽
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);

        // 设置行高
        $sheet->getRowDimension('1')->setRowHeight(30);

        // 设置数据行样式
        $sheet->getStyle('2:4')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D3D3D3']
                ]
            ]
        ]);

        return [];
    }
}
