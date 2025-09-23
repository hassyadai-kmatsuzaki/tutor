import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  Paper,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  InputAdornment,
} from '@mui/material';
import { Save, Cancel } from '@mui/icons-material';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { propertyApi } from '../../services/api';
import { Property, PropertyFormData } from '../../types';

interface PropertyFormProps {
  property?: Property;
  onSave: () => void;
  onCancel: () => void;
}

const PropertyForm: React.FC<PropertyFormProps> = ({ property, onSave, onCancel }) => {
  const queryClient = useQueryClient();
  const isEditing = !!property;

  const [formData, setFormData] = useState<PropertyFormData>({
    property_code: '',
    property_name: '',
    property_type: '',
    manager_name: '',
    registration_date: new Date().toISOString().split('T')[0],
    address: '',
    information_source: '',
    transaction_category: '',
    land_area: undefined,
    building_area: undefined,
    structure_floors: '',
    construction_year: '',
    price: 0,
    price_per_unit: undefined,
    current_profit: undefined,
    prefecture: '',
    city: '',
    nearest_station: '',
    walking_minutes: undefined,
    remarks: '',
    status: 'available',
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (property) {
      setFormData({
        property_code: property.property_code || '',
        property_name: property.property_name,
        property_type: property.property_type,
        manager_name: property.creator?.name || '',
        registration_date: property.created_at.split('T')[0],
        address: `${property.prefecture}${property.city}`,
        information_source: '',
        transaction_category: property.transaction_category,
        land_area: property.land_area,
        building_area: property.building_area,
        structure_floors: property.structure_floors || '',
        construction_year: property.construction_year || '',
        price: property.price,
        price_per_unit: property.price_per_unit,
        current_profit: property.current_profit,
        prefecture: property.prefecture,
        city: property.city,
        nearest_station: property.nearest_station || '',
        walking_minutes: property.walking_minutes,
        remarks: property.remarks || '',
        status: property.status || 'available',
      });
    }
  }, [property]);

  const createMutation = useMutation({
    mutationFn: (data: PropertyFormData) => propertyApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['properties'] });
      onSave();
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: PropertyFormData) => propertyApi.update(property!.id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['properties'] });
      queryClient.invalidateQueries({ queryKey: ['property', property!.id] });
      onSave();
    },
  });

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.property_name.trim()) {
      newErrors.property_name = '物件名は必須です';
    }
    if (!formData.property_type) {
      newErrors.property_type = '物件種別は必須です';
    }
    if (!formData.transaction_category) {
      newErrors.transaction_category = '取引区分は必須です';
    }
    if (!formData.price || formData.price <= 0) {
      newErrors.price = '価格は必須です';
    }
    if (!formData.prefecture) {
      newErrors.prefecture = '都道府県は必須です';
    }
    if (!formData.city) {
      newErrors.city = '市区町村は必須です';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    if (isEditing) {
      updateMutation.mutate(formData);
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleChange = (field: keyof PropertyFormData) => (
    event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement> | any
  ) => {
    const value = event.target.value;
    setFormData(prev => ({
      ...prev,
      [field]: value,
    }));
    
    // エラーをクリア
    if (errors[field]) {
      setErrors(prev => ({
        ...prev,
        [field]: '',
      }));
    }
  };

  const propertyTypes = [
    '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', 'マンション', 'アパート', '戸建て', 'その他'
  ];

  const transactionCategories = [
    '売買', '賃貸', '事業用定期借地権', 'その他'
  ];

  const prefectures = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
  ];

  const statuses = [
    { value: 'available', label: '販売中' },
    { value: 'under_negotiation', label: '商談中' },
    { value: 'sold', label: '売却済み' },
    { value: 'pending', label: '保留中' },
  ];

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        {isEditing ? '物件編集' : '新規物件登録'}
      </Typography>

      <Paper sx={{ p: 3 }}>
        <form onSubmit={handleSubmit}>
          <Grid container spacing={3}>
            {/* 基本情報 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom>基本情報</Typography>
            </Grid>
            
            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="物件コード"
                value={formData.property_code}
                onChange={handleChange('property_code')}
                helperText="コードが指定されている場合、同じコードの物件を更新します"
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                required
                label="物件名"
                value={formData.property_name}
                onChange={handleChange('property_name')}
                error={!!errors.property_name}
                helperText={errors.property_name}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth required error={!!errors.property_type}>
                <InputLabel>物件種別</InputLabel>
                <Select
                  value={formData.property_type}
                  label="物件種別"
                  onChange={handleChange('property_type')}
                >
                  {propertyTypes.map(type => (
                    <MenuItem key={type} value={type}>{type}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth required error={!!errors.transaction_category}>
                <InputLabel>取引区分</InputLabel>
                <Select
                  value={formData.transaction_category}
                  label="取引区分"
                  onChange={handleChange('transaction_category')}
                >
                  {transactionCategories.map(category => (
                    <MenuItem key={category} value={category}>{category}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth>
                <InputLabel>ステータス</InputLabel>
                <Select
                  value={formData.status}
                  label="ステータス"
                  onChange={handleChange('status')}
                >
                  {statuses.map(status => (
                    <MenuItem key={status.value} value={status.value}>{status.label}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            {/* 面積・構造 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>面積・構造</Typography>
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="土地面積"
                type="number"
                value={formData.land_area || ''}
                onChange={handleChange('land_area')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">㎡</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="建物面積"
                type="number"
                value={formData.building_area || ''}
                onChange={handleChange('building_area')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">㎡</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="構造・階数"
                value={formData.structure_floors}
                onChange={handleChange('structure_floors')}
                placeholder="例: RC造3階建て"
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="築年"
                value={formData.construction_year}
                onChange={handleChange('construction_year')}
                placeholder="例: 2020年"
              />
            </Grid>

            {/* 価格情報 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>価格情報</Typography>
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                required
                label="販売価格"
                type="number"
                value={formData.price}
                onChange={handleChange('price')}
                error={!!errors.price}
                helperText={errors.price}
                InputProps={{
                  endAdornment: <InputAdornment position="end">円</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                label="単価"
                type="number"
                value={formData.price_per_unit || ''}
                onChange={handleChange('price_per_unit')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">円/㎡</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                label="現在利益"
                type="number"
                value={formData.current_profit || ''}
                onChange={handleChange('current_profit')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">円</InputAdornment>,
                }}
              />
            </Grid>

            {/* 立地情報 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>立地情報</Typography>
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth required error={!!errors.prefecture}>
                <InputLabel>都道府県</InputLabel>
                <Select
                  value={formData.prefecture}
                  label="都道府県"
                  onChange={handleChange('prefecture')}
                >
                  {prefectures.map(prefecture => (
                    <MenuItem key={prefecture} value={prefecture}>{prefecture}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                required
                label="市区町村"
                value={formData.city}
                onChange={handleChange('city')}
                error={!!errors.city}
                helperText={errors.city}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="最寄り駅"
                value={formData.nearest_station}
                onChange={handleChange('nearest_station')}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="徒歩"
                type="number"
                value={formData.walking_minutes || ''}
                onChange={handleChange('walking_minutes')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">分</InputAdornment>,
                }}
              />
            </Grid>

            {/* 備考 */}
            <Grid item xs={12}>
              <TextField
                fullWidth
                multiline
                rows={4}
                label="備考"
                value={formData.remarks}
                onChange={handleChange('remarks')}
              />
            </Grid>

            {/* ボタン */}
            <Grid item xs={12}>
              <Box sx={{ display: 'flex', gap: 2, mt: 2 }}>
                <Button
                  type="submit"
                  variant="contained"
                  startIcon={<Save />}
                  disabled={createMutation.isPending || updateMutation.isPending}
                >
                  {isEditing ? '更新' : '登録'}
                </Button>
                <Button
                  variant="outlined"
                  startIcon={<Cancel />}
                  onClick={onCancel}
                  disabled={createMutation.isPending || updateMutation.isPending}
                >
                  キャンセル
                </Button>
              </Box>
            </Grid>
          </Grid>
        </form>
      </Paper>
    </Box>
  );
};

export default PropertyForm; 