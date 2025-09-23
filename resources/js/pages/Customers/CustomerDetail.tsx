import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Typography,
  Button,
  Paper,
  Grid,
  Chip,
  Divider,
  IconButton,
  Menu,
  MenuItem,
} from '@mui/material';
import {
  ArrowBack,
  Edit,
  Delete,
  MoreVert,
  Person,
  Business,
  LocationOn,
  AttachMoney,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { customerApi } from '../../services/api';
import { Customer } from '../../types';
import LoadingSpinner from '../../components/Common/LoadingSpinner';
import ErrorAlert from '../../components/Common/ErrorAlert';
import ConfirmDialog from '../../components/Common/ConfirmDialog';
import CustomerForm from '../../components/Customers/CustomerForm';

const CustomerDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  const [isEditing, setIsEditing] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);

  const customerId = parseInt(id || '0');

  const { data: customer, isLoading, error } = useQuery({
    queryKey: ['customer', customerId],
    queryFn: () => customerApi.getById(customerId),
    select: (response) => response.data.data,
    enabled: !!customerId,
  });

  const deleteMutation = useMutation({
    mutationFn: () => customerApi.delete(customerId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      navigate('/customers');
    },
  });

  const handleMenuClick = (event: React.MouseEvent<HTMLElement>) => {
    setAnchorEl(event.currentTarget);
  };

  const handleMenuClose = () => {
    setAnchorEl(null);
  };

  const handleEdit = () => {
    setIsEditing(true);
    handleMenuClose();
  };

  const handleDelete = () => {
    setShowDeleteDialog(true);
    handleMenuClose();
  };

  const confirmDelete = () => {
    deleteMutation.mutate();
    setShowDeleteDialog(false);
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

  const getStatusColor = (status?: string) => {
    switch (status) {
      case 'active': return 'success';
      case 'negotiating': return 'warning';
      case 'closed': return 'primary';
      case 'suspended': return 'default';
      default: return 'default';
    }
  };

  const getStatusLabel = (status?: string) => {
    switch (status) {
      case 'active': return 'アクティブ';
      case 'negotiating': return '商談中';
      case 'closed': return '成約済み';
      case 'suspended': return '保留中';
      default: return status;
    }
  };

  const getCustomerTypeLabel = (type?: string) => {
    switch (type) {
      case '法人': return '法人';
      case '個人': return '個人';
      default: return type;
    }
  };

  const getPriorityColor = (priority?: string) => {
    switch (priority) {
      case '高': return 'error';
      case '中': return 'warning';
      case '低': return 'default';
      default: return 'default';
    }
  };

  if (isLoading) {
    return <LoadingSpinner message="顧客情報を読み込み中..." />;
  }

  if (error) {
    return (
      <ErrorAlert 
        title="顧客情報の読み込みに失敗しました"
        message="APIサーバーが起動していることを確認してください。"
        onRetry={() => queryClient.invalidateQueries({ queryKey: ['customer', customerId] })}
      />
    );
  }

  if (!customer) {
    return (
      <Box>
        <Typography variant="h6">顧客が見つかりません</Typography>
        <Button startIcon={<ArrowBack />} onClick={() => navigate('/customers')}>
          顧客一覧に戻る
        </Button>
      </Box>
    );
  }

  if (isEditing) {
    return (
      <CustomerForm
        customer={customer}
        onSave={() => {
          setIsEditing(false);
          queryClient.invalidateQueries({ queryKey: ['customer', customerId] });
        }}
        onCancel={() => setIsEditing(false)}
      />
    );
  }

  return (
    <Box>
      {/* ヘッダー */}
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
          <IconButton onClick={() => navigate('/customers')}>
            <ArrowBack />
          </IconButton>
          <Typography variant="h4">
            {customer.customer_name}
          </Typography>
          <Chip 
            label={getCustomerTypeLabel(customer.customer_type)} 
            size="small"
            variant="outlined"
          />
          <Chip 
            label={getStatusLabel(customer.status)} 
            color={getStatusColor(customer.status) as any}
            size="small"
          />
        </Box>
        <Box>
          <IconButton onClick={handleMenuClick}>
            <MoreVert />
          </IconButton>
          <Menu
            anchorEl={anchorEl}
            open={Boolean(anchorEl)}
            onClose={handleMenuClose}
          >
            <MenuItem onClick={handleEdit}>
              <Edit sx={{ mr: 1 }} />
              編集
            </MenuItem>
            <MenuItem onClick={handleDelete} sx={{ color: 'error.main' }}>
              <Delete sx={{ mr: 1 }} />
              削除
            </MenuItem>
          </Menu>
        </Box>
      </Box>

      <Grid container spacing={3}>
        {/* 基本情報 */}
        <Grid item xs={12} md={8}>
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              {customer.customer_type === '法人' ? <Business sx={{ mr: 1, verticalAlign: 'middle' }} /> : <Person sx={{ mr: 1, verticalAlign: 'middle' }} />}
              基本情報
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Grid container spacing={2}>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">顧客名</Typography>
                <Typography variant="body1">{customer.customer_name}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">顧客種別</Typography>
                <Typography variant="body1">{customer.customer_type}</Typography>
              </Grid>
              {customer.contact_person && (
                <Grid item xs={6}>
                  <Typography variant="body2" color="textSecondary">担当者</Typography>
                  <Typography variant="body1">{customer.contact_person}</Typography>
                </Grid>
              )}
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">電話番号</Typography>
                <Typography variant="body1">{customer.phone || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">メールアドレス</Typography>
                <Typography variant="body1">{customer.email || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">住所</Typography>
                <Typography variant="body1">{customer.address || '-'}</Typography>
              </Grid>
            </Grid>
          </Paper>

          {/* 希望条件 */}
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              <LocationOn sx={{ mr: 1, verticalAlign: 'middle' }} />
              希望条件
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Grid container spacing={2}>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">希望エリア</Typography>
                <Typography variant="body1">{customer.area_preference || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">希望物件種別</Typography>
                <Typography variant="body1">{customer.property_type_preference || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">利回り要求</Typography>
                <Typography variant="body1">
                  {customer.yield_requirement ? `${customer.yield_requirement}%以上` : '-'}
                </Typography>
              </Grid>
              <Grid item xs={12}>
                <Typography variant="body2" color="textSecondary">詳細要求</Typography>
                <Typography variant="body1" sx={{ whiteSpace: 'pre-wrap' }}>
                  {customer.detailed_requirements || '-'}
                </Typography>
              </Grid>
            </Grid>
          </Paper>
        </Grid>

        {/* 予算・管理情報 */}
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              <AttachMoney sx={{ mr: 1, verticalAlign: 'middle' }} />
              予算情報
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Box sx={{ mb: 2 }}>
              <Typography variant="body2" color="textSecondary">予算</Typography>
              <Typography variant="h6" color="primary" fontWeight="bold">
                {formatBudget(customer.budget_min, customer.budget_max)}
              </Typography>
            </Box>
          </Paper>

          {/* 管理情報 */}
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>管理情報</Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">優先度</Typography>
              <Chip 
                label={customer.priority} 
                color={getPriorityColor(customer.priority) as any}
                size="small"
              />
            </Box>
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">担当者</Typography>
              <Typography variant="body1">{customer.assigned_user?.name || '-'}</Typography>
            </Box>
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">最終連絡日</Typography>
              <Typography variant="body1">
                {customer.last_contact_date 
                  ? new Date(customer.last_contact_date).toLocaleDateString('ja-JP')
                  : '-'
                }
              </Typography>
            </Box>
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">次回連絡予定日</Typography>
              <Typography variant="body1">
                {customer.next_contact_date 
                  ? new Date(customer.next_contact_date).toLocaleDateString('ja-JP')
                  : '-'
                }
              </Typography>
            </Box>
          </Paper>

          {/* 登録情報 */}
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom>登録情報</Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">登録日</Typography>
              <Typography variant="body1">
                {new Date(customer.created_at).toLocaleDateString('ja-JP')}
              </Typography>
            </Box>
            
            <Box>
              <Typography variant="body2" color="textSecondary">更新日</Typography>
              <Typography variant="body1">
                {new Date(customer.updated_at).toLocaleDateString('ja-JP')}
              </Typography>
            </Box>
          </Paper>
        </Grid>
      </Grid>

      {/* 削除確認ダイアログ */}
      <ConfirmDialog
        open={showDeleteDialog}
        title="顧客を削除"
        message={`「${customer.customer_name}」を削除してもよろしいですか？この操作は取り消せません。`}
        confirmText="削除"
        onConfirm={confirmDelete}
        onCancel={() => setShowDeleteDialog(false)}
        severity="error"
      />
    </Box>
  );
};

export default CustomerDetail; 