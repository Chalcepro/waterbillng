// Export Utilities for Admin Dashboard

class ExportUtils {
    // Format date for filenames
    static getFormattedDate() {
        const now = new Date();
        return now.toISOString().slice(0, 19).replace(/[:.]/g, '-');
    }

    // Download file helper
    static downloadFile(content, fileName, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Format data as CSV
    static toCSV(data, headers) {
        if (!data || !data.length) return '';
        
        // Use provided headers or get from first object
        const headerRow = headers || Object.keys(data[0]);
        
        // Escape CSV values
        const escapeCsv = (val) => {
            if (val === null || val === undefined) return '';
            const str = String(val);
            if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                return `"${str.replace(/"/g, '""')}"`;
            }
            return str;
        };
        
        // Build CSV content
        const rows = [
            headerRow.join(','),
            ...data.map(row => 
                headerRow.map(field => escapeCsv(row[field] || '')).join(',')
            )
        ];
        
        return rows.join('\n');
    }

    // Format data as JSON
    static toJSON(data, pretty = true) {
        return pretty ? JSON.stringify(data, null, 2) : JSON.stringify(data);
    }

    // Format data as TXT (tab-separated)
    static toTXT(data, headers) {
        if (!data || !data.length) return '';
        
        const headerRow = headers || Object.keys(data[0]);
        
        const rows = [
            headerRow.join('\t'),
            ...data.map(row => 
                headerRow.map(field => String(row[field] || '').replace(/\t/g, ' ')).join('\t')
            )
        ];
        
        return rows.join('\n');
    }

    // Generate PDF using jsPDF
    static async toPDF(title, data, headers, fileName = null) {
        // Dynamically import jsPDF to reduce initial load
        const { jsPDF } = await import('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
        const { autoTable } = await import('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js');
        
        const doc = new jsPDF();
        
        // Add title
        doc.setFontSize(18);
        doc.text(title, 14, 22);
        
        // Add date
        doc.setFontSize(10);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 30);
        
        // Prepare data for autoTable
        const tableColumn = headers || Object.keys(data[0] || {});
        const tableRows = data.map(item => 
            tableColumn.map(key => item[key] || '')
        );
        
        // Add table
        doc.autoTable({
            head: [tableColumn],
            body: tableRows,
            startY: 40,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [41, 128, 185] },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { top: 40 }
        });
        
        // Save or return the PDF
        if (fileName === null) {
            return doc.output('datauristring');
        }
        
        doc.save(fileName || `export-${this.getFormattedDate()}.pdf`);
        return null;
    }

    // Generate Word document using docx
    static async toDOCX(title, data, headers) {
        // Dynamically import docx to reduce initial load
        const { Document, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, AlignmentType } = 
            await import('https://cdn.jsdelivr.net/npm/docx@7.8.2/build/index.min.js');
        
        // Prepare table rows
        const tableHeaders = new TableRow({
            children: (headers || Object.keys(data[0] || {})).map(header => 
                new TableCell({
                    children: [new Paragraph({
                        children: [new TextRun({ text: String(header), bold: true })]
                    })]
                })
            )
        });

        const tableRows = data.map(item => 
            new TableRow({
                children: (headers || Object.keys(item)).map(key => 
                    new TableCell({
                        children: [new Paragraph(String(item[key] || ''))]
                    })
                )
            })
        );

        const doc = new Document({
            sections: [{
                properties: {},
                children: [
                    new Paragraph({
                        text: title,
                        heading: 'Heading1',
                        alignment: AlignmentType.CENTER
                    }),
                    new Paragraph({
                        text: `Generated on: ${new Date().toLocaleString()}`,
                        spacing: { after: 400 }
                    }),
                    new Table({
                        width: { size: 100, type: WidthType.PERCENTAGE },
                        rows: [tableHeaders, ...tableRows],
                    })
                ]
            }]
        });

        // Generate and download the document
        const blob = await docx.Packer.toBlob(doc);
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `export-${this.getFormattedDate()}.docx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Export data in multiple formats
    static async exportData(data, format, title = 'Export', headers = null) {
        const dateStr = this.getFormattedDate();
        const fileName = `${title.toLowerCase().replace(/\s+/g, '-')}-${dateStr}`;
        
        switch (format.toLowerCase()) {
            case 'csv':
                this.downloadFile(
                    this.toCSV(data, headers),
                    `${fileName}.csv`,
                    'text/csv;charset=utf-8;'
                );
                break;
                
            case 'json':
                this.downloadFile(
                    this.toJSON(data, true),
                    `${fileName}.json`,
                    'application/json'
                );
                break;
                
            case 'txt':
                this.downloadFile(
                    this.toTXT(data, headers),
                    `${fileName}.txt`,
                    'text/plain'
                );
                break;
                
            case 'pdf':
                await this.toPDF(title, data, headers, `${fileName}.pdf`);
                break;
                
            case 'docx':
                await this.toDOCX(title, data, headers);
                break;
                
            default:
                throw new Error(`Unsupported export format: ${format}`);
        }
    }
}

// Make ExportUtils available globally
window.ExportUtils = ExportUtils;
