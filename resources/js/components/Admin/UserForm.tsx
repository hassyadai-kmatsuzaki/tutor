import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Grid,
  Paper,
  FormControlLabel,
  Switch,
  Alert,
} from '@mui/material';
import { Save as SaveIcon, Cancel as CancelIcon } from '@mui/icons-material';
import { useMutation } from '@tanstack/react-query';
import { userApi } from '../../services/api';

interface UserFormProps {
  user?: any;
  onSave: () => void;
  onCancel: () => void;
}

const UserForm: React.FC<UserFormProps> = ({ user, onSave, onCancel }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'sales',
    department: '',
    phone: '',
    is_active: true,
  });
  const [errors, setErrors] = useState<any>({});

  useEffect(() => {
    if (user) {
      setFormData({
        name: user.name || '',
        email: user.email || '',
        password: '',
        password_confirmation: '',
        role: user.role || 'sales',
        department: user.department || '',
        phone: user.phone || '',
        is_active: user.is_active ?? true,
      });
    }
  }, [user]);

  const createMutation = useMutation({
    mutationFn: (data: any) => userApi.create(data),
    onSuccess: () => {
      onSave();
    },
    onError: (error: any) => {
      setErrors(error.response?.data?.errors || {});
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: any) => userApi.update(user.id, data),
    onSuccess: () => {
      onSave();
    },
    onError: (error: any) => {
      setErrors(error.response?.data?.errors || {});
    },
  });

  const handleChange = (field: string) => (event: any) => {
    const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
    setFormData(prev => ({
      ...prev,
      [field]: value,
    }));
    
    // エラーをクリア
    if (errors[field]) {
      setErrors(prev => ({
        ...prev,
        [field]: undefined,
      }));
    }
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    
    // バリデーション
    const newErrors: any = {};
    if (!formData.name.trim()) newErrors.name = '名前は必須です';
    if (!formData.email.trim()) newErrors.email = 'メールアドレスは必須です';
    if (!user && !formData.password) newErrors.password = 'パスワードは必須です';
    if (formData.password && formData.password !== formData.password_confirmation) {
      newErrors.password_confirmation = 'パスワードが一致しません';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    if (user) {
      updateMutation.mutate(formData);
    } else {
      createMutation.mutate(formData);
    }
  };

  const isLoading = createMutation.isPending || updateMutation.isPending;

  return (
    <Box sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {user ? 'ユーザー編集' : '新規ユーザー作成'}
      </Typography>

      <Paper sx={{ p: 3, mt: 3 }}>
        <form onSubmit={handleSubmit}>
          <Grid container spacing={3}>
            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                required
                label="名前"
                value={formData.name}
                onChange={handleChange('name')}
                error={!!errors.name}
                helperText={errors.name}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                required
                type="email"
                label="メールアドレス"
                value={formData.email}
                onChange={handleChange('email')}
                error={!!errors.email}
                helperText={errors.email}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                type="password"
                label={user ? 'パスワード（変更する場合のみ）' : 'パスワード'}
                value={formData.password}
                onChange={handleChange('password')}
                error={!!errors.password}
                helperText={errors.password}
                required={!user}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                type="password"
                label="パスワード確認"
                value={formData.password_confirmation}
                onChange={handleChange('password_confirmation')}
                error={!!errors.password_confirmation}
                helperText={errors.password_confirmation}
                required={!!formData.password}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl fullWidth required>
                <InputLabel>ロール</InputLabel>
                <Select
                  value={formData.role}
                  label="ロール"
                  onChange={handleChange('role')}
                  disabled={isLoading}
                >
                  <MenuItem value="admin">管理者</MenuItem>
                  <MenuItem value="manager">マネージャー</MenuItem>
                  <MenuItem value="sales">営業</MenuItem>
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="部署"
                value={formData.department}
                onChange={handleChange('department')}
                error={!!errors.department}
                helperText={errors.department}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <TextField
                fullWidth
                label="電話番号"
                value={formData.phone}
                onChange={handleChange('phone')}
                error={!!errors.phone}
                helperText={errors.phone}
                disabled={isLoading}
              />
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControlLabel
                control={
                  <Switch
                    checked={formData.is_active}
                    onChange={handleChange('is_active')}
                    disabled={isLoading}
                  />
                }
                label="アクティブ"
              />
            </Grid>

            {Object.keys(errors).length > 0 && !errors.name && !errors.email && !errors.password && !errors.password_confirmation && (
              <Grid item xs={12}>
                <Alert severity="error">
                  入力内容を確認してください
                </Alert>
              </Grid>
            )}

            <Grid item xs={12}>
              <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                <Button
                  variant="outlined"
                  onClick={onCancel}
                  disabled={isLoading}
                  startIcon={<CancelIcon />}
                >
                  キャンセル
                </Button>
                <Button
                  type="submit"
                  variant="contained"
                  disabled={isLoading}
                  startIcon={<SaveIcon />}
                >
                  {user ? '更新' : '作成'}
                </Button>
              </Box>
            </Grid>
          </Grid>
        </form>
      </Paper>
    </Box>
  );
};

export default UserForm; 