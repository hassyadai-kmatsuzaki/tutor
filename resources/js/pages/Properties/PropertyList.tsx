import React, { useState } from 'react';
import {
  Box,
  Typography,
  Button,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  IconButton,
  TextField,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
} from '@mui/material';
import { useTheme } from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Visibility as VisibilityIcon,
  Search as SearchIcon,
  Upload as UploadIcon,
} from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { propertyApi } from '../../services/api';
import PropertyForm from '../../components/Properties/PropertyForm';
import CSVImportDialog from '../../components/Common/CSVImportDialog';
import { Property, PropertyFilters } from '../../types';

const PropertyList: React.FC = () => {
  const navigate = useNavigate();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const [filters, setFilters] = useState<PropertyFilters>({
    page: 1,
    per_page: 15,
  });
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [showImportDialog, setShowImportDialog] = useState(false);
  const [editingProperty, setEditingProperty] = useState<Property | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ['properties', filters],
    queryFn: () => propertyApi.getList(filters),
    select: (response) => response.data.data,
  });

  const handleCSVImport = async (file: File): Promise<any> => {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await propertyApi.import(file);
    return response.data;
  };

  const templateData = [
    {
      property_code: 'P001',
      property_name: 'サンプル物件',
      property_type: '店舗',
      transaction_category: '売買',
      prefecture: '東京都',
      city: '渋谷区',
      price: 50000000,
      land_area: 100,
      building_area: 80,
      status: 'available',
    }
  ];

  const handleFilterChange = (field: keyof PropertyFilters, value: any) => {
    setFilters(prev => ({
      ...prev,
      [field]: value,
      page: 1, // フィルター変更時はページをリセット
    }));
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'available':
        return 'success';
      case 'reserved':
        return 'warning';
      case 'sold':
        return 'error';
      case 'suspended':
        return 'default';
      default:
        return 'default';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'available':
        return '販売中';
      case 'reserved':
        return '商談中';
      case 'sold':
        return '成約済み';
      case 'suspended':
        return '一時停止';
      default:
        return status;
    }
  };

  if (isLoading) {
    return (
      <Box sx={{ p: 3 }}>
        <Typography>読み込み中...</Typography>
      </Box>
    );
  }

  if (error) {
    return (
      <Box sx={{ p: 3 }}>
        <Typography color="error">エラーが発生しました</Typography>
      </Box>
    );
  }

  if (showCreateForm) {
    return (
      <PropertyForm
        onSave={() => setShowCreateForm(false)}
        onCancel={() => setShowCreateForm(false)}
      />
    );
  }

  if (editingProperty) {
    return (
      <PropertyForm
        property={editingProperty}
        onSave={() => setEditingProperty(null)}
        onCancel={() => setEditingProperty(null)}
      />
    );
  }

  return (
    <Box sx={{ px: { xs: 1, sm: 2 } }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4">物件管理</Typography>
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
            startIcon={<AddIcon />}
            onClick={() => setShowCreateForm(true)}
          >
            新規登録
          </Button>
        </Box>
      </Box>

      {/* 検索・フィルター */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Grid container spacing={2} alignItems="center">
          <Grid item xs={12} md={3}>
            <TextField
              fullWidth
              label="物件名検索"
              value={filters.property_name || ''}
              onChange={(e) => handleFilterChange('property_name', e.target.value)}
              InputProps={{
                endAdornment: <SearchIcon />,
              }}
            />
          </Grid>
          <Grid item xs={12} md={2}>
            <FormControl fullWidth>
              <InputLabel>種別</InputLabel>
              <Select
                value={filters.property_type || ''}
                label="種別"
                onChange={(e) => handleFilterChange('property_type', e.target.value)}
              >
                <MenuItem value="">全て</MenuItem>
                <MenuItem value="店舗">店舗</MenuItem>
                <MenuItem value="レジ">レジ</MenuItem>
                <MenuItem value="土地">土地</MenuItem>
                <MenuItem value="事務所">事務所</MenuItem>
                <MenuItem value="区分">区分</MenuItem>
                <MenuItem value="一棟ビル">一棟ビル</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={2}>
            <FormControl fullWidth>
              <InputLabel>都道府県</InputLabel>
              <Select
                value={filters.prefecture || ''}
                label="都道府県"
                onChange={(e) => handleFilterChange('prefecture', e.target.value)}
              >
                <MenuItem value="">全て</MenuItem>
                <MenuItem value="東京都">東京都</MenuItem>
                <MenuItem value="大阪府">大阪府</MenuItem>
                <MenuItem value="神奈川県">神奈川県</MenuItem>
                <MenuItem value="愛知県">愛知県</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={2}>
            <FormControl fullWidth>
              <InputLabel>ステータス</InputLabel>
              <Select
                value={filters.status || ''}
                label="ステータス"
                onChange={(e) => handleFilterChange('status', e.target.value)}
              >
                <MenuItem value="">全て</MenuItem>
                <MenuItem value="available">販売中</MenuItem>
                <MenuItem value="reserved">商談中</MenuItem>
                <MenuItem value="sold">成約済み</MenuItem>
                <MenuItem value="suspended">一時停止</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={3}>
            <Box sx={{ display: 'flex', gap: 1 }}>
              <TextField
                label="価格下限（万円）"
                type="number"
                value={filters.price_min || ''}
                onChange={(e) => handleFilterChange('price_min', Number(e.target.value) || undefined)}
                sx={{ width: '50%' }}
              />
              <TextField
                label="価格上限（万円）"
                type="number"
                value={filters.price_max || ''}
                onChange={(e) => handleFilterChange('price_max', Number(e.target.value) || undefined)}
                sx={{ width: '50%' }}
              />
            </Box>
          </Grid>
        </Grid>
      </Paper>

      {/* 一覧：モバイルはカード、PCはテーブル */}
      {isMobile ? (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
          {data?.data.map((property: Property) => (
            <Paper
              key={property.id}
              variant="outlined"
              onClick={() => navigate(`/properties/${property.id}`)}
              sx={{ p: 1.5, borderRadius: 2, cursor: 'pointer' }}
            >
              <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: .5 }}>
                <Typography variant="subtitle2" fontWeight={700} sx={{ mr: 1 }}>
                  {property.property_name}
                </Typography>
                {property.status && (
                  <Chip
                    label={getStatusLabel(property.status)}
                    color={getStatusColor(property.status) as any}
                    size="small"
                    sx={{ height: 22 }}
                  />
                )}
              </Box>
              <Typography variant="body2" color="text.secondary" sx={{ mb: .5 }}>
                {property.address || '-'}
              </Typography>
              <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap' }}>
                {property.property_type && (
                  <Chip label={property.property_type} size="small" variant="outlined" />
                )}
                <Typography variant="body2">
                  価格: {property.price != null ? `${property.price.toLocaleString()}万円` : '-'}
                </Typography>
                <Typography variant="body2">
                  利回り: {property.current_profit != null ? `${property.current_profit}%` : '-'}
                </Typography>
                <Typography variant="body2">
                  担当: {property.creator?.name || property.manager_name || '-'}
                </Typography>
              </Box>
            </Paper>
          ))}
        </Box>
      ) : (
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>物件名</TableCell>
                <TableCell>種別</TableCell>
                <TableCell>所在地</TableCell>
                <TableCell align="right">価格（万円）</TableCell>
                <TableCell align="right">利回り（%）</TableCell>
                <TableCell>ステータス</TableCell>
                <TableCell>担当者</TableCell>
                <TableCell align="center">操作</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {data?.data.map((property: Property) => (
                <TableRow 
                  key={property.id} 
                  hover 
                  onClick={() => navigate(`/properties/${property.id}`)}
                  sx={{ cursor: 'pointer' }}
                >
                  <TableCell>
                    <Typography variant="body2" fontWeight="medium">
                      {property.property_name}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    {property.property_type ? (
                      <Chip label={property.property_type} size="small" />
                    ) : (
                      <Typography variant="body2" color="textSecondary">-</Typography>
                    )}
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {property.address || '-'}
                    </Typography>
                  </TableCell>
                  <TableCell align="right">
                    <Typography variant="body2" fontWeight="medium">
                      {property.price != null ? property.price.toLocaleString() : '-'}
                    </Typography>
                  </TableCell>
                  <TableCell align="right">
                    {property.current_profit ? (
                      <Typography variant="body2">
                        {property.current_profit}%
                      </Typography>
                    ) : (
                      <Typography variant="body2" color="textSecondary">
                        -
                      </Typography>
                    )}
                  </TableCell>
                  <TableCell>
                    {property.status ? (
                      <Chip
                        label={getStatusLabel(property.status)}
                        color={getStatusColor(property.status) as any}
                        size="small"
                      />
                    ) : (
                      <Typography variant="body2" color="textSecondary">-</Typography>
                    )}
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2">
                      {property.creator?.name || property.manager_name}
                    </Typography>
                  </TableCell>
                  <TableCell align="center">
                    <IconButton
                      size="small"
                      onClick={(e) => {
                        e.stopPropagation();
                        navigate(`/properties/${property.id}`);
                      }}
                    >
                      <VisibilityIcon />
                    </IconButton>
                    <IconButton
                      size="small"
                      onClick={(e) => {
                        e.stopPropagation();
                        setEditingProperty(property);
                      }}
                    >
                      <EditIcon />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      {/* ページネーション情報 */}
      {data && (
        <Box sx={{ mt: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="body2" color="textSecondary">
            {data.from}-{data.to} / {data.total}件
          </Typography>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Button
              variant="outlined"
              size="small"
              disabled={!data.prev_page_url}
              onClick={() => setFilters(prev => ({ ...prev, page: Math.max(1, (prev.page || 1) - 1) }))}
            >
              前へ
            </Button>
            <Typography variant="body2" color="textSecondary">
              ページ {data.current_page} / {data.last_page}
            </Typography>
            <Button
              variant="outlined"
              size="small"
              disabled={!data.next_page_url}
              onClick={() => setFilters(prev => ({ ...prev, page: (prev.page || 1) + 1 }))}
            >
              次へ
            </Button>
          </Box>
        </Box>
      )}

      {/* CSVインポートダイアログ */}
      <CSVImportDialog
        open={showImportDialog}
        title="物件CSVインポート"
        onClose={() => setShowImportDialog(false)}
        onImport={handleCSVImport}
        templateData={templateData}
        templateFilename="properties_template.csv"
      />
    </Box>
  );
};

export default PropertyList; 