import React, { useState } from 'react';
import {
  Box,
  Typography,
  Button,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  IconButton,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Grid,
  Card,
  CardContent,
  CardActions,
  useTheme,
  useMediaQuery,
  Stack,
  Divider,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Search as SearchIcon,
  Person as PersonIcon,
  AdminPanelSettings as AdminIcon,
  SupervisorAccount as ManagerIcon,
  SupportAgent as SalesIcon,
} from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { userApi } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import UserForm from '../../components/Admin/UserForm';
import LoadingSpinner from '../../components/Common/LoadingSpinner';
import ErrorAlert from '../../components/Common/ErrorAlert';
import ConfirmDialog from '../../components/Common/ConfirmDialog';

const UserManagement: React.FC = () => {
  const { user: currentUser } = useAuth();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingUser, setEditingUser] = useState<any>(null);
  const [deletingUser, setDeletingUser] = useState<any>(null);

  // 管理者権限チェック
  if (!currentUser?.is_admin) {
    return (
      <Box sx={{ p: 3 }}>
        <ErrorAlert 
          title="アクセス拒否"
          message="この機能は管理者のみ利用可能です"
        />
      </Box>
    );
  }

  const { data: usersData, isLoading, error, refetch } = useQuery({
    queryKey: ['users', { search: searchTerm, role: roleFilter, is_active: statusFilter }],
    queryFn: () => userApi.getList({
      search: searchTerm || undefined,
      role: roleFilter || undefined,
      is_active: statusFilter || undefined,
    }),
    select: (response) => response.data,
  });

  const { data: statisticsData } = useQuery({
    queryKey: ['user-statistics'],
    queryFn: () => userApi.getStatistics(),
    select: (response) => response.data.data,
  });

  const getRoleIcon = (role: string) => {
    switch (role) {
      case 'admin': return <AdminIcon fontSize="small" />;
      case 'manager': return <ManagerIcon fontSize="small" />;
      case 'sales': return <SalesIcon fontSize="small" />;
      default: return <PersonIcon fontSize="small" />;
    }
  };

  const getRoleLabel = (role: string) => {
    switch (role) {
      case 'admin': return '管理者';
      case 'manager': return 'マネージャー';
      case 'sales': return '営業';
      default: return role;
    }
  };

  const getRoleColor = (role: string) => {
    switch (role) {
      case 'admin': return 'error';
      case 'manager': return 'warning';
      case 'sales': return 'primary';
      default: return 'default';
    }
  };

  const handleDelete = async () => {
    if (!deletingUser) return;
    
    try {
      await userApi.delete(deletingUser.id);
      refetch();
      setDeletingUser(null);
    } catch (error) {
      console.error('Delete error:', error);
    }
  };

  if (showCreateForm) {
    return (
      <UserForm
        onSave={() => {
          setShowCreateForm(false);
          refetch();
        }}
        onCancel={() => setShowCreateForm(false)}
      />
    );
  }

  if (editingUser) {
    return (
      <UserForm
        user={editingUser}
        onSave={() => {
          setEditingUser(null);
          refetch();
        }}
        onCancel={() => setEditingUser(null)}
      />
    );
  }

  if (isLoading) return <LoadingSpinner message="ユーザー情報を読み込み中..." />;
  if (error) return <ErrorAlert title="エラー" message="ユーザー情報の取得に失敗しました" />;

  return (
    <Box sx={{ p: { xs: 2, md: 3 } }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: isMobile ? 'flex-start' : 'center', mb: 3, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 2 : 0 }}>
        <Typography variant={isMobile ? 'h5' : 'h4'}>ユーザー管理</Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => setShowCreateForm(true)}
          size={isMobile ? 'small' : 'medium'}
          fullWidth={isMobile}
        >
          新規ユーザー作成
        </Button>
      </Box>

      {/* 統計情報 */}
      {statisticsData && (
        <Grid container spacing={3} sx={{ mb: 3 }}>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="primary">
                  総ユーザー数
                </Typography>
                <Typography variant="h4">
                  {statisticsData.total_users}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="success.main">
                  アクティブ
                </Typography>
                <Typography variant="h4">
                  {statisticsData.active_users}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="warning.main">
                  非アクティブ
                </Typography>
                <Typography variant="h4">
                  {statisticsData.inactive_users}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="info.main">
                  管理者
                </Typography>
                <Typography variant="h4">
                  {statisticsData.by_role?.admin || 0}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      {/* 検索・フィルター */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Grid container spacing={2} alignItems="center">
          <Grid item xs={12} sm={6} md={4}>
            <TextField
              fullWidth
              label="検索（名前・メール・部署）"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              InputProps={{
                startAdornment: <SearchIcon sx={{ mr: 1, color: 'text.secondary' }} />,
              }}
              size={isMobile ? 'small' : 'medium'}
            />
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <FormControl fullWidth size={isMobile ? 'small' : 'medium'}>
              <InputLabel>ロール</InputLabel>
              <Select
                value={roleFilter}
                label="ロール"
                onChange={(e) => setRoleFilter(e.target.value)}
              >
                <MenuItem value="">すべて</MenuItem>
                <MenuItem value="admin">管理者</MenuItem>
                <MenuItem value="manager">マネージャー</MenuItem>
                <MenuItem value="sales">営業</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <FormControl fullWidth size={isMobile ? 'small' : 'medium'}>
              <InputLabel>ステータス</InputLabel>
              <Select
                value={statusFilter}
                label="ステータス"
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <MenuItem value="">すべて</MenuItem>
                <MenuItem value="true">アクティブ</MenuItem>
                <MenuItem value="false">非アクティブ</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6} md={2}>
            <Button
              fullWidth
              variant="outlined"
              onClick={() => {
                setSearchTerm('');
                setRoleFilter('');
                setStatusFilter('');
              }}
              size={isMobile ? 'small' : 'medium'}
            >
              リセット
            </Button>
          </Grid>
        </Grid>
      </Paper>

      {/* ユーザー一覧 */}
      {isMobile ? (
        <Box sx={{ mb: 3 }}>
          {usersData?.data?.data?.length === 0 ? (
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <Typography>ユーザーが見つかりませんでした</Typography>
            </Box>
          ) : (
            <Stack spacing={2}>
              {usersData?.data?.data?.map((user: any) => (
                <Card key={user.id}>
                  <CardContent sx={{ pb: 1 }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
                      {getRoleIcon(user.role)}
                      <Typography variant="h6" fontWeight="bold">{user.name}</Typography>
                    </Box>
                    <Typography variant="body2">{user.email}</Typography>
                    <Divider sx={{ my: 1 }} />
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, alignItems: 'center' }}>
                      <Chip label={getRoleLabel(user.role)} color={getRoleColor(user.role) as any} size="small" />
                      <Chip label={user.is_active ? 'アクティブ' : '非アクティブ'} color={user.is_active ? 'success' : 'default'} size="small" />
                    </Box>
                    <Box sx={{ mt: 1 }}>
                      <Typography variant="body2">部署: {user.department || '-'}</Typography>
                      <Typography variant="body2">電話: {user.phone || '-'}</Typography>
                      <Typography variant="caption" color="textSecondary">登録: {new Date(user.created_at).toLocaleDateString('ja-JP')}</Typography>
                    </Box>
                  </CardContent>
                  <CardActions sx={{ pt: 0, px: 2, pb: 2 }}>
                    <Button size="small" variant="outlined" startIcon={<EditIcon />} onClick={() => setEditingUser(user)} fullWidth>
                      編集
                    </Button>
                    {user.id !== currentUser.id && (
                      <Button size="small" variant="outlined" color="error" startIcon={<DeleteIcon />} onClick={() => setDeletingUser(user)} fullWidth>
                        無効化
                      </Button>
                    )}
                  </CardActions>
                </Card>
              ))}
            </Stack>
          )}
        </Box>
      ) : (
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>名前</TableCell>
                <TableCell>メールアドレス</TableCell>
                <TableCell>ロール</TableCell>
                <TableCell>部署</TableCell>
                <TableCell>電話番号</TableCell>
                <TableCell>ステータス</TableCell>
                <TableCell>登録日</TableCell>
                <TableCell align="center">操作</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {usersData?.data?.data?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={8} align="center">
                    ユーザーが見つかりませんでした
                  </TableCell>
                </TableRow>
              ) : (
                usersData?.data?.data?.map((user: any) => (
                  <TableRow key={user.id} hover>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        {getRoleIcon(user.role)}
                        <Typography variant="body2" fontWeight="medium">
                          {user.name}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {user.email}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getRoleLabel(user.role)}
                        color={getRoleColor(user.role) as any}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {user.department || '-'}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {user.phone || '-'}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={user.is_active ? 'アクティブ' : '非アクティブ'}
                        color={user.is_active ? 'success' : 'default'}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {new Date(user.created_at).toLocaleDateString('ja-JP')}
                      </Typography>
                    </TableCell>
                    <TableCell align="center">
                      <IconButton
                        size="small"
                        onClick={() => setEditingUser(user)}
                        color="primary"
                      >
                        <EditIcon />
                      </IconButton>
                      {user.id !== currentUser.id && (
                        <IconButton
                          size="small"
                          onClick={() => setDeletingUser(user)}
                          color="error"
                        >
                          <DeleteIcon />
                        </IconButton>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      {/* 削除確認ダイアログ */}
      <ConfirmDialog
        open={!!deletingUser}
        title="ユーザーの無効化"
        message={`${deletingUser?.name}さんを無効化しますか？この操作により、ユーザーはログインできなくなります。`}
        onConfirm={handleDelete}
        onCancel={() => setDeletingUser(null)}
        severity="warning"
      />
    </Box>
  );
};

export default UserManagement; 