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
  Home,
  LocationOn,
  AttachMoney,
  TrendingUp,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { propertyApi } from '../../services/api';
import { Property } from '../../types';
import LoadingSpinner from '../../components/Common/LoadingSpinner';
import ErrorAlert from '../../components/Common/ErrorAlert';
import ConfirmDialog from '../../components/Common/ConfirmDialog';
import PropertyForm from '../../components/Properties/PropertyForm';

const PropertyDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  const [isEditing, setIsEditing] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);

  const propertyId = parseInt(id || '0');

  const { data: property, isLoading, error } = useQuery({
    queryKey: ['property', propertyId],
    queryFn: () => propertyApi.getById(propertyId),
    select: (response) => response.data.data,
    enabled: !!propertyId,
  });

  const deleteMutation = useMutation({
    mutationFn: () => propertyApi.delete(propertyId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['properties'] });
      navigate('/properties');
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

  const formatPrice = (price?: number | null) => {
    if (price == null) return '-';
    if (price >= 100000000) {
      return `${(price / 100000000).toFixed(1)}億円`;
    }
    return `${(price / 10000).toLocaleString()}万円`;
  };

  const formatArea = (area?: number | null, unit: string = '㎡') => {
    if (area == null) return '-';
    return `${area.toLocaleString()}${unit}`;
  };

  const getStatusColor = (status?: string) => {
    switch (status) {
      case 'available': return 'success';
      case 'under_negotiation': return 'warning';
      case 'sold': return 'default';
      case 'pending': return 'info';
      default: return 'default';
    }
  };

  const getStatusLabel = (status?: string) => {
    switch (status) {
      case 'available': return '販売中';
      case 'under_negotiation': return '商談中';
      case 'sold': return '売却済み';
      case 'pending': return '保留中';
      default: return status;
    }
  };

  if (isLoading) {
    return <LoadingSpinner message="物件情報を読み込み中..." />;
  }

  if (error) {
    return (
      <ErrorAlert 
        title="物件情報の読み込みに失敗しました"
        message="APIサーバーが起動していることを確認してください。"
        onRetry={() => queryClient.invalidateQueries({ queryKey: ['property', propertyId] })}
      />
    );
  }

  if (!property) {
    return (
      <Box>
        <Typography variant="h6">物件が見つかりません</Typography>
        <Button startIcon={<ArrowBack />} onClick={() => navigate('/properties')}>
          物件一覧に戻る
        </Button>
      </Box>
    );
  }

  if (isEditing) {
    return (
      <PropertyForm
        property={property}
        onSave={() => {
          setIsEditing(false);
          queryClient.invalidateQueries({ queryKey: ['property', propertyId] });
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
          <IconButton onClick={() => navigate('/properties')}>
            <ArrowBack />
          </IconButton>
          <Typography variant="h4">
            {property.property_name}
          </Typography>
          <Chip 
            label={getStatusLabel(property.status)} 
            color={getStatusColor(property.status) as any}
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
              <Home sx={{ mr: 1, verticalAlign: 'middle' }} />
              基本情報
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Grid container spacing={2}>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">物件種別</Typography>
                <Typography variant="body1">{property.property_type}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">取引区分</Typography>
                <Typography variant="body1">{property.transaction_category}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">土地面積</Typography>
                <Typography variant="body1">{formatArea(property.land_area)}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">建物面積</Typography>
                <Typography variant="body1">{formatArea(property.building_area)}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">構造・階数</Typography>
                <Typography variant="body1">{property.structure_floors || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">築年</Typography>
                <Typography variant="body1">{property.construction_year || '-'}</Typography>
              </Grid>
            </Grid>
          </Paper>

          {/* 立地情報 */}
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              <LocationOn sx={{ mr: 1, verticalAlign: 'middle' }} />
              立地情報
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Grid container spacing={2}>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">都道府県</Typography>
                <Typography variant="body1">{property.prefecture || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">市区町村</Typography>
                <Typography variant="body1">{property.city || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">最寄り駅</Typography>
                <Typography variant="body1">{property.nearest_station || '-'}</Typography>
              </Grid>
              <Grid item xs={6}>
                <Typography variant="body2" color="textSecondary">徒歩</Typography>
                <Typography variant="body1">
                  {property.walking_minutes ? `${property.walking_minutes}分` : '-'}
                </Typography>
              </Grid>
            </Grid>
          </Paper>

          {/* 備考 */}
          {property.remarks && (
            <Paper sx={{ p: 3 }}>
              <Typography variant="h6" gutterBottom>備考</Typography>
              <Divider sx={{ mb: 2 }} />
              <Typography variant="body1" sx={{ whiteSpace: 'pre-wrap' }}>
                {property.remarks}
              </Typography>
            </Paper>
          )}
        </Grid>

        {/* 価格情報 */}
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 3, mb: 3 }}>
            <Typography variant="h6" gutterBottom>
              <AttachMoney sx={{ mr: 1, verticalAlign: 'middle' }} />
              価格情報
            </Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Box sx={{ mb: 2 }}>
              <Typography variant="body2" color="textSecondary">販売価格</Typography>
              <Typography variant="h5" color="primary" fontWeight="bold">
                {formatPrice(property.price)}
              </Typography>
            </Box>
            
            {property.price_per_unit && (
              <Box sx={{ mb: 2 }}>
                <Typography variant="body2" color="textSecondary">単価</Typography>
                <Typography variant="body1">
                  {formatPrice(property.price_per_unit)}/㎡
                </Typography>
              </Box>
            )}
            
            {property.current_profit && (
              <Box>
                <Typography variant="body2" color="textSecondary">現在利益</Typography>
                <Typography variant="body1" color="success.main">
                  <TrendingUp sx={{ mr: 0.5, verticalAlign: 'middle', fontSize: '1rem' }} />
                  {formatPrice(property.current_profit)}
                </Typography>
              </Box>
            )}
          </Paper>

          {/* 作成者情報 */}
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom>登録情報</Typography>
            <Divider sx={{ mb: 2 }} />
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">登録者</Typography>
              <Typography variant="body1">{property.creator?.name || '-'}</Typography>
            </Box>
            
            <Box sx={{ mb: 1 }}>
              <Typography variant="body2" color="textSecondary">登録日</Typography>
              <Typography variant="body1">
                {new Date(property.created_at).toLocaleDateString('ja-JP')}
              </Typography>
            </Box>
            
            <Box>
              <Typography variant="body2" color="textSecondary">更新日</Typography>
              <Typography variant="body1">
                {new Date(property.updated_at).toLocaleDateString('ja-JP')}
              </Typography>
            </Box>
          </Paper>
        </Grid>
      </Grid>

      {/* 削除確認ダイアログ */}
      <ConfirmDialog
        open={showDeleteDialog}
        title="物件を削除"
        message={`「${property.property_name}」を削除してもよろしいですか？この操作は取り消せません。`}
        confirmText="削除"
        onConfirm={confirmDelete}
        onCancel={() => setShowDeleteDialog(false)}
        severity="error"
      />
    </Box>
  );
};

export default PropertyDetail; 