import React, { useState, useRef } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Typography,
  Box,
  Alert,
  LinearProgress,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
} from '@mui/material';
import { Upload, Download, Close } from '@mui/icons-material';

interface CSVImportDialogProps {
  open: boolean;
  title: string;
  onClose: () => void;
  onImport: (file: File) => Promise<any>;
  templateData?: any[];
  templateFilename?: string;
}

const CSVImportDialog: React.FC<CSVImportDialogProps> = ({
  open,
  title,
  onClose,
  onImport,
  templateData = [],
  templateFilename = 'template.csv',
}) => {
  const [file, setFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadResult, setUploadResult] = useState<any>(null);
  const [error, setError] = useState<string>('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = event.target.files?.[0];
    if (selectedFile) {
      if (selectedFile.type !== 'text/csv' && !selectedFile.name.endsWith('.csv')) {
        setError('CSVファイルを選択してください');
        return;
      }
      setFile(selectedFile);
      setError('');
      setUploadResult(null);
    }
  };

  const handleUpload = async () => {
    if (!file) {
      setError('ファイルを選択してください');
      return;
    }

    setIsUploading(true);
    setError('');

    try {
      const result = await onImport(file);
      setUploadResult(result);
    } catch (err: any) {
      setError(err.message || 'インポートに失敗しました');
    } finally {
      setIsUploading(false);
    }
  };

  const downloadTemplate = () => {
    if (templateData.length === 0) return;

    const headers = Object.keys(templateData[0]);
    const csvContent = [
      headers.join(','),
      ...templateData.map(row => 
        headers.map(header => `"${row[header] || ''}"`).join(',')
      )
    ].join('\n');

    // BOM付きUTF-8で文字化けを防ぐ
    const bom = '\uFEFF';
    const csvWithBom = bom + csvContent;
    
    const blob = new Blob([csvWithBom], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = templateFilename;
    link.click();
  };

  const handleClose = () => {
    setFile(null);
    setError('');
    setUploadResult(null);
    setIsUploading(false);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    onClose();
  };

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="md" fullWidth>
      <DialogTitle>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {title}
          <Button
            startIcon={<Close />}
            onClick={handleClose}
            size="small"
          >
            閉じる
          </Button>
        </Box>
      </DialogTitle>

      <DialogContent>
        {/* テンプレートダウンロード */}
        {templateData.length > 0 && (
          <Box sx={{ mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              1. テンプレートをダウンロード
            </Typography>
            <Button
              variant="outlined"
              startIcon={<Download />}
              onClick={downloadTemplate}
            >
              テンプレートをダウンロード
            </Button>
          </Box>
        )}

        {/* ファイルアップロード */}
        <Box sx={{ mb: 3 }}>
          <Typography variant="h6" gutterBottom>
            2. CSVファイルを選択
          </Typography>
          <input
            ref={fileInputRef}
            type="file"
            accept=".csv"
            onChange={handleFileSelect}
            style={{ display: 'none' }}
          />
          <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
            <Button
              variant="outlined"
              startIcon={<Upload />}
              onClick={() => fileInputRef.current?.click()}
            >
              ファイルを選択
            </Button>
            {file && (
              <Typography variant="body2">
                選択されたファイル: {file.name}
              </Typography>
            )}
          </Box>
        </Box>

        {/* エラー表示 */}
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        {/* 進行状況 */}
        {isUploading && (
          <Box sx={{ mb: 2 }}>
            <Typography variant="body2" gutterBottom>
              インポート中...
            </Typography>
            <LinearProgress />
          </Box>
        )}

        {/* 結果表示 */}
        {uploadResult && (
          <Box sx={{ mb: 2 }}>
            <Alert severity="success" sx={{ mb: 2 }}>
              インポートが完了しました
            </Alert>
            
            {uploadResult.summary && (
              <Box sx={{ mb: 2 }}>
                <Typography variant="h6" gutterBottom>
                  インポート結果
                </Typography>
                <Typography variant="body2">
                  成功: {uploadResult.summary.success}件
                </Typography>
                <Typography variant="body2">
                  失敗: {uploadResult.summary.failed}件
                </Typography>
                <Typography variant="body2">
                  更新: {uploadResult.summary.updated}件
                </Typography>
                <Typography variant="body2">
                  新規: {uploadResult.summary.created}件
                </Typography>
              </Box>
            )}

            {uploadResult.errors && uploadResult.errors.length > 0 && (
              <Box>
                <Typography variant="h6" gutterBottom color="error">
                  エラー詳細
                </Typography>
                <TableContainer component={Paper} sx={{ maxHeight: 300 }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell>行</TableCell>
                        <TableCell>エラー内容</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {uploadResult.errors.map((error: any, index: number) => (
                        <TableRow key={index}>
                          <TableCell>{error.row}</TableCell>
                          <TableCell>{error.message}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
            )}
          </Box>
        )}
      </DialogContent>

      <DialogActions>
        <Button onClick={handleClose}>
          キャンセル
        </Button>
        <Button
          variant="contained"
          onClick={handleUpload}
          disabled={!file || isUploading}
          startIcon={<Upload />}
        >
          インポート実行
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default CSVImportDialog; 