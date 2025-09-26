import React, { useState } from 'react';
import {
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  TextField,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
  Alert,
} from '@mui/material';
import { Search, Add, Upload as UploadIcon, Edit as EditIcon } from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { customerApi } from '../../services/api';
import { Customer } from '../../types';
import CustomerForm from '../../components/Customers/CustomerForm';
import CSVImportDialog from '../../components/Common/CSVImportDialog';

const CustomerList: React.FC = () => {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [customerTypeFilter, setCustomerTypeFilter] = useState('');
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [showImportDialog, setShowImportDialog] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState<Customer | null>(null);

  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(15);

  const { data: customersData, isLoading, error } = useQuery({
    queryKey: ['customers', { status: statusFilter, customer_type: customerTypeFilter, page, per_page: perPage }],
    queryFn: () => customerApi.getList({
      status: statusFilter || undefined,
      customer_type: customerTypeFilter || undefined,
      page,
      per_page: perPage,
    }),
    select: (response) => response.data,
  });

  const handleCSVImport = async (file: File): Promise<any> => {
    const response = await customerApi.import(file);
    return response.data;
  };

  const templateData = [
    {
      customer_code: 'C001',
      customer_name: 'サンプル顧客',
      customer_type: '法人',
      area_preference: '東京都',
      budget_min: 10000000,
      budget_max: 50000000,
      priority: '高',
      status: 'active',
    }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'success';
      case 'potential': return 'warning';
      case 'inactive': return 'default';
      case 'contracted': return 'primary';
      default: return 'default';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'active': return 'アクティブ';
      case 'potential': return '見込み';
      case 'inactive': return '非アクティブ';
      case 'contracted': return '契約済み';
      default: return status;
    }
  };

  const getCustomerTypeLabel = (type: string) => {
    switch (type) {
      case 'individual': return '個人';
      case 'corporation': return '法人';
      default: return type;
    }
  };

  const formatBudget = (min?: number, max?: number) => {
    if (!min && !max) return '-';
    if (min && max) {
      return `${(min / 10000).toLocaleString()}万円 ～ ${(max / 10000).toLocaleString()}万円`;
    }
    if (min) return `${(min / 10000).toLocaleString()}万円以上`;
    if (max) return `${(max / 10000).toLocaleString()}万円以下`;
    return '-';
  };

  if (error) {
    return (
      <Box>
        <Typography variant="h4" gutterBottom>
          顧客管理
        </Typography>
        <Alert severity="error">
          顧客データの読み込みに失敗しました。APIサーバーが起動していることを確認してください。
        </Alert>
      </Box>
    );
  }

  if (showCreateForm) {
    return (
      <CustomerForm
        onSave={() => setShowCreateForm(false)}
        onCancel={() => setShowCreateForm(false)}
      />
    );
  }

  if (editingCustomer) {
    return (
      <CustomerForm
        customer={editingCustomer}
        onSave={() => setEditingCustomer(null)}
        onCancel={() => setEditingCustomer(null)}
      />
    );
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4">
          顧客管理
        </Typography>
        <Box sx={{ display: 'flex', gap: 1 }}>
          <Button
            variant="outlined"
            startIcon={<UploadIcon />}
            onClick={() => setShowImportDialog(true)}
          >
            CSVインポート
          </Button>
          <Button 
            variant="contained" 
            startIcon={<Add />}
            onClick={() => setShowCreateForm(true)}
          >
            新規顧客登録
          </Button>
        </Box>
      </Box>

      {/* 検索・フィルター */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Grid container spacing={2}>
          <Grid item xs={12} md={4}>
            <TextField
              fullWidth
              label="顧客名・会社名で検索"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              InputProps={{
                startAdornment: <Search sx={{ mr: 1, color: 'action.active' }} />,
              }}
            />
          </Grid>
          <Grid item xs={12} md={3}>
            <FormControl fullWidth>
              <InputLabel>ステータス</InputLabel>
              <Select
                value={statusFilter}
                label="ステータス"
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <MenuItem value="">すべて</MenuItem>
                <MenuItem value="active">アクティブ</MenuItem>
                <MenuItem value="potential">見込み</MenuItem>
                <MenuItem value="inactive">非アクティブ</MenuItem>
                <MenuItem value="contracted">契約済み</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={3}>
            <FormControl fullWidth>
              <InputLabel>顧客種別</InputLabel>
              <Select
                value={customerTypeFilter}
                label="顧客種別"
                onChange={(e) => setCustomerTypeFilter(e.target.value)}
              >
                <MenuItem value="">すべて</MenuItem>
                <MenuItem value="individual">個人</MenuItem>
                <MenuItem value="corporation">法人</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={2}>
            <Button
              fullWidth
              variant="outlined"
              onClick={() => {
                setSearchTerm('');
                setStatusFilter('');
                setCustomerTypeFilter('');
              }}
              sx={{ height: '56px' }}
            >
              クリア
            </Button>
          </Grid>
        </Grid>
      </Paper>

      {/* 顧客一覧テーブル */}
      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>顧客名</TableCell>
              <TableCell>種別</TableCell>
              <TableCell>ステータス</TableCell>
              <TableCell>予算</TableCell>
              <TableCell>希望エリア</TableCell>
              <TableCell>担当者</TableCell>
              <TableCell>登録日</TableCell>
              <TableCell align="center">操作</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={8} align="center">
                  読み込み中...
                </TableCell>
              </TableRow>
                          ) : customersData?.data?.data?.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} align="center">
                  顧客が見つかりませんでした
                </TableCell>
              </TableRow>
            ) : (
              customersData?.data?.data?.map((customer: Customer) => (
                <TableRow 
                  key={customer.id} 
                  hover 
                  onClick={() => navigate(`/customers/${customer.id}`)}
                  sx={{ cursor: 'pointer' }}
                >
                  <TableCell>
                    <Box>
                      <Typography variant="body2" fontWeight="medium">
                        {customer.customer_name}
                      </Typography>
                                              {customer.customer_type === '法人' && customer.contact_person && (
                        <Typography variant="caption" color="textSecondary">
                          担当: {customer.contact_person}
                        </Typography>
                      )}
                    </Box>
                  </TableCell>
                  <TableCell>
                    <Chip 
                      label={getCustomerTypeLabel(customer.customer_type)} 
                      size="small"
                      variant="outlined"
                    />
                  </TableCell>
                  <TableCell>
                    <Chip 
                      label={getStatusLabel(customer.status)} 
                      size="small"
                      color={getStatusColor(customer.status) as any}
                    />
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {formatBudget(customer.budget_min, customer.budget_max)}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {customer.area_preference || '-'}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {customer.assigned_user?.name || '-'}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {new Date(customer.created_at).toLocaleDateString('ja-JP')}
                    </Typography>
                  </TableCell>
                  <TableCell align="center">
                    <Button
                      size="small"
                      startIcon={<EditIcon />}
                      onClick={(e) => {
                        e.stopPropagation();
                        setEditingCustomer(customer);
                      }}
                    >
                      編集
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </TableContainer>

      {/* 統計情報 */}
      {customersData?.data && (
        <Box sx={{ mt: 2 }}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Typography variant="body2" color="textSecondary">
              {customersData.data.from}-{customersData.data.to} / {customersData.data.total}件
            </Typography>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Button
                variant="outlined"
                size="small"
                disabled={!customersData.data.prev_page_url}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                前へ
              </Button>
              <Typography variant="body2" color="textSecondary">
                ページ {customersData.data.current_page} / {customersData.data.last_page}
              </Typography>
              <Button
                variant="outlined"
                size="small"
                disabled={!customersData.data.next_page_url}
                onClick={() => setPage((p) => p + 1)}
              >
                次へ
              </Button>
            </Box>
          </Box>
        </Box>
      )}

      {/* CSVインポートダイアログ */}
      <CSVImportDialog
        open={showImportDialog}
        title="顧客CSVインポート"
        onClose={() => setShowImportDialog(false)}
        onImport={handleCSVImport}
        templateData={templateData}
        templateFilename="customers_template.csv"
      />
    </Box>
  );
};

export default CustomerList; 