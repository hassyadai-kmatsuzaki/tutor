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
import { customerApi } from '../../services/api';
import { Customer, CustomerFormData } from '../../types';

interface CustomerFormProps {
  customer?: Customer;
  onSave: () => void;
  onCancel: () => void;
}

const CustomerForm: React.FC<CustomerFormProps> = ({ customer, onSave, onCancel }) => {
  const queryClient = useQueryClient();
  const isEditing = !!customer;

  const [formData, setFormData] = useState<CustomerFormData>({
    customer_code: '',
    customer_name: '',
    customer_type: '個人',
    area_preference: '',
    property_type_preference: '',
    detailed_requirements: '',
    budget_min: undefined,
    budget_max: undefined,
    yield_requirement: undefined,
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    priority: '中',
    status: 'active',
    last_contact_date: '',
    next_contact_date: '',
    assigned_to: 1, // デフォルト値、実際は現在のユーザーIDを設定
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (customer) {
      setFormData({
        customer_code: customer.customer_code || '',
        customer_name: customer.customer_name,
        customer_type: customer.customer_type,
        area_preference: customer.area_preference || '',
        property_type_preference: customer.property_type_preference || '',
        detailed_requirements: customer.detailed_requirements || '',
        budget_min: customer.budget_min,
        budget_max: customer.budget_max,
        yield_requirement: customer.yield_requirement,
        contact_person: customer.contact_person || '',
        phone: customer.phone || '',
        email: customer.email || '',
        address: customer.address || '',
        priority: customer.priority,
        status: customer.status,
        last_contact_date: customer.last_contact_date || '',
        next_contact_date: customer.next_contact_date || '',
        assigned_to: customer.assigned_to,
      });
    }
  }, [customer]);

  const createMutation = useMutation({
    mutationFn: (data: CustomerFormData) => customerApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      onSave();
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: CustomerFormData) => customerApi.update(customer!.id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      queryClient.invalidateQueries({ queryKey: ['customer', customer!.id] });
      onSave();
    },
  });

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.customer_name.trim()) {
      newErrors.customer_name = '顧客名は必須です';
    }
    if (!formData.customer_type) {
      newErrors.customer_type = '顧客種別は必須です';
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

  const handleChange = (field: keyof CustomerFormData) => (
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

  const customerTypes = [
    '個人', '法人', '自社', 'エンド法人', 'エンド（中国系）', '飲食経営者', '不動明屋', '半法商事'
  ];

  const priorities = [
    { value: '高', label: '高' },
    { value: '中', label: '中' },
    { value: '低', label: '低' },
  ];

  const statuses = [
    { value: 'active', label: 'アクティブ' },
    { value: 'negotiating', label: '商談中' },
    { value: 'closed', label: '成約済み' },
    { value: 'suspended', label: '保留中' },
  ];

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        {isEditing ? '顧客編集' : '新規顧客登録'}
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
                label="顧客コード"
                value={formData.customer_code}
                onChange={handleChange('customer_code')}
                helperText="コードが指定されている場合、同じコードの顧客を更新します"
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                required
                label="顧客名"
                value={formData.customer_name}
                onChange={handleChange('customer_name')}
                error={!!errors.customer_name}
                helperText={errors.customer_name}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth required error={!!errors.customer_type}>
                <InputLabel>顧客種別</InputLabel>
                <Select
                  value={formData.customer_type}
                  label="顧客種別"
                  onChange={handleChange('customer_type')}
                >
                  {customerTypes.map(type => (
                    <MenuItem key={type} value={type}>{type}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="担当者"
                value={formData.contact_person}
                onChange={handleChange('contact_person')}
                helperText="法人の場合は担当者名を入力"
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="電話番号"
                value={formData.phone}
                onChange={handleChange('phone')}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="メールアドレス"
                type="email"
                value={formData.email}
                onChange={handleChange('email')}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="住所"
                value={formData.address}
                onChange={handleChange('address')}
              />
            </Grid>

            {/* 希望条件 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>希望条件</Typography>
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="希望エリア"
                value={formData.area_preference}
                onChange={handleChange('area_preference')}
                placeholder="例: 東京都渋谷区"
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="希望物件種別"
                value={formData.property_type_preference}
                onChange={handleChange('property_type_preference')}
                placeholder="例: 店舗、事務所"
              />
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                label="予算下限"
                type="number"
                value={formData.budget_min || ''}
                onChange={handleChange('budget_min')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">円</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                label="予算上限"
                type="number"
                value={formData.budget_max || ''}
                onChange={handleChange('budget_max')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">円</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12} md={4}>
              <TextField
                fullWidth
                label="利回り要求"
                type="number"
                value={formData.yield_requirement || ''}
                onChange={handleChange('yield_requirement')}
                InputProps={{
                  endAdornment: <InputAdornment position="end">%</InputAdornment>,
                }}
              />
            </Grid>

            <Grid item xs={12}>
              <TextField
                fullWidth
                multiline
                rows={4}
                label="詳細要求"
                value={formData.detailed_requirements}
                onChange={handleChange('detailed_requirements')}
                placeholder="その他の詳細な要求事項を記載してください"
              />
            </Grid>

            {/* 管理情報 */}
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>管理情報</Typography>
            </Grid>

            <Grid item xs={12} md={4}>
              <FormControl fullWidth>
                <InputLabel>優先度</InputLabel>
                <Select
                  value={formData.priority}
                  label="優先度"
                  onChange={handleChange('priority')}
                >
                  {priorities.map(priority => (
                    <MenuItem key={priority.value} value={priority.value}>{priority.label}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={4}>
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

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="最終連絡日"
                type="date"
                value={formData.last_contact_date}
                onChange={handleChange('last_contact_date')}
                InputLabelProps={{
                  shrink: true,
                }}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="次回連絡予定日"
                type="date"
                value={formData.next_contact_date}
                onChange={handleChange('next_contact_date')}
                InputLabelProps={{
                  shrink: true,
                }}
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

export default CustomerForm; 