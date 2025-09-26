import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
  LinearProgress,
  Card,
  CardContent,
  CardActions,
  Divider,
  useMediaQuery,
  useTheme,
  Stack,
} from '@mui/material';
import { Search, TrendingUp, Refresh, Visibility } from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { matchApi } from '../../services/api';
import { PropertyMatch } from '../../types';

const MatchingList: React.FC = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [statusFilter, setStatusFilter] = useState('');
  const [minScore, setMinScore] = useState('');
  const [propertyName, setPropertyName] = useState('');
  const [customerName, setCustomerName] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(15);

  const { data: matchesData, isLoading, error } = useQuery({
    queryKey: ['matches', { status: statusFilter, min_score: minScore, property_name: propertyName, customer_name: customerName, page, per_page: perPage }],
    queryFn: () => matchApi.getList({
      status: statusFilter || undefined,
      min_score: minScore ? parseInt(minScore) : undefined,
      property_name: propertyName || undefined,
      customer_name: customerName || undefined,
      page,
      per_page: perPage,
    }),
    select: (response) => response.data,
  });

  const generateMutation = useMutation({
    mutationFn: async () => {
      const score = minScore ? parseInt(minScore) : undefined;
      return matchApi.generate(score !== undefined ? { min_score: score } : undefined);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['matches'] });
    },
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'warning';
      case 'presented': return 'info';
      case 'interested': return 'success';
      case 'not_interested': return 'default';
      case 'contracted': return 'primary';
      default: return 'default';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'pending': return '未提案';
      case 'presented': return '提案済み';
      case 'interested': return '興味あり';
      case 'not_interested': return '興味なし';
      case 'contracted': return '契約済み';
      default: return status;
    }
  };

  const getScoreColor = (score: number) => {
    if (score >= 80) return 'success';
    if (score >= 60) return 'warning';
    return 'default';
  };

  const formatPrice = (price?: number) => {
    if (!price) return '-';
    if (price >= 100000000) {
      return `${(price / 100000000).toFixed(1)}億円`;
    }
    return `${(price / 10000).toLocaleString()}万円`;
  };

  if (error) {
    return (
      <Box>
        <Typography variant="h4" gutterBottom>
          マッチング管理
        </Typography>
        <Alert severity="error">
          マッチングデータの読み込みに失敗しました。APIサーバーが起動していることを確認してください。
        </Alert>
      </Box>
    );
  }

  return (
    <Box sx={{ px: { xs: 1, sm: 2 } }}>
      {/* ヘッダー */}
      <Box sx={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: isMobile ? 'flex-start' : 'center', 
        mb: 3,
        flexDirection: isMobile ? 'column' : 'row',
        gap: isMobile ? 2 : 0
      }}>
        <Typography variant={isMobile ? "h5" : "h4"}>
          マッチング管理
        </Typography>
        <Button 
          variant="contained" 
          startIcon={<Refresh />} 
          onClick={() => generateMutation.mutate()}
          disabled={generateMutation.isPending}
          size={isMobile ? "small" : "medium"}
          fullWidth={isMobile}
        >
          {generateMutation.isPending ? '再計算中...' : 'マッチング再計算'}
        </Button>
      </Box>

      {/* 検索・フィルター */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Grid container spacing={2}>
          <Grid item xs={12} sm={6} md={3}>
            <FormControl fullWidth size={isMobile ? "small" : "medium"}>
              <InputLabel>ステータス</InputLabel>
              <Select
                value={statusFilter}
                label="ステータス"
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <MenuItem value="">すべて</MenuItem>
                <MenuItem value="pending">未提案</MenuItem>
                <MenuItem value="presented">提案済み</MenuItem>
                <MenuItem value="interested">興味あり</MenuItem>
                <MenuItem value="not_interested">興味なし</MenuItem>
                <MenuItem value="contracted">契約済み</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6} md={2}>
            <TextField
              fullWidth
              label="最小スコア"
              type="number"
              value={minScore}
              onChange={(e) => setMinScore(e.target.value)}
              inputProps={{ min: 0, max: 100 }}
              size={isMobile ? "small" : "medium"}
            />
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <TextField
              fullWidth
              label="物件名で検索"
              value={propertyName}
              onChange={(e) => setPropertyName(e.target.value)}
              size={isMobile ? "small" : "medium"}
            />
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <TextField
              fullWidth
              label="顧客名で検索"
              value={customerName}
              onChange={(e) => setCustomerName(e.target.value)}
              size={isMobile ? "small" : "medium"}
            />
          </Grid>
          <Grid item xs={12} sm={6} md={1}>
            <Button
              fullWidth
              variant="outlined"
              onClick={() => {
                setStatusFilter('');
                setMinScore('');
                setPropertyName('');
                setCustomerName('');
                setPage(1);
                setPerPage(15);
              }}
              size={isMobile ? "small" : "medium"}
            >
              クリア
            </Button>
          </Grid>
        </Grid>
      </Paper>

      {/* マッチング一覧 */}
      {isMobile ? (
        // モバイル: カード表示
        <Box sx={{ mb: 3 }}>
          {isLoading ? (
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <Typography>読み込み中...</Typography>
            </Box>
          ) : matchesData?.data?.data?.length === 0 ? (
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <Typography>マッチングが見つかりませんでした</Typography>
            </Box>
          ) : (
            <Stack spacing={2}>
              {matchesData?.data?.data?.map((match: PropertyMatch) => (
                <Card key={match.id} sx={{ width: '100%' }}>
                  <CardContent sx={{ pb: 1 }}>
                    {/* 物件情報 */}
                    <Box sx={{ mb: 2 }}>
                      <Typography variant="h6" fontWeight="bold" sx={{ mb: 1 }}>
                        {match.property?.property_name || `物件ID: ${match.property_id}`}
                      </Typography>
                      <Typography variant="body2" color="textSecondary" sx={{ mb: 1 }}>
                        {match.property?.prefecture} {match.property?.city}
                      </Typography>
                      <Typography variant="body2">
                        価格: {formatPrice(match.property?.price)}
                      </Typography>
                    </Box>

                    <Divider sx={{ my: 1 }} />

                    {/* 顧客情報 */}
                    <Box sx={{ mb: 2 }}>
                      <Typography variant="subtitle1" fontWeight="medium">
                        {match.customer?.customer_name || `顧客ID: ${match.customer_id}`}
                      </Typography>
                      <Typography variant="body2" color="textSecondary" sx={{ mb: 1 }}>
                        {match.customer?.customer_type}
                      </Typography>
                      <Typography variant="body2">
                        予算: {match.customer?.budget_min && match.customer?.budget_max 
                          ? `${formatPrice(match.customer.budget_min)} ～ ${formatPrice(match.customer.budget_max)}`
                          : match.customer?.budget_min 
                            ? `${formatPrice(match.customer.budget_min)}以上`
                            : match.customer?.budget_max
                              ? `${formatPrice(match.customer.budget_max)}以下`
                              : '-'
                        }
                      </Typography>
                    </Box>

                    <Divider sx={{ my: 1 }} />

                    {/* マッチング情報 */}
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Chip 
                          label={`${match.match_score}%`}
                          size="small"
                          color={getScoreColor(match.match_score) as any}
                          icon={<TrendingUp />}
                        />
                        <Box sx={{ width: 80 }}>
                          <LinearProgress 
                            variant="determinate" 
                            value={match.match_score} 
                            color={getScoreColor(match.match_score) as any}
                            sx={{ height: 6, borderRadius: 3 }}
                          />
                        </Box>
                      </Box>
                      <Chip 
                        label={getStatusLabel(match.status)} 
                        size="small"
                        color={getStatusColor(match.status) as any}
                      />
                    </Box>

                    {/* 日付情報 */}
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem', color: 'text.secondary' }}>
                      <Typography variant="caption">
                        提案: {match.presented_at 
                          ? new Date(match.presented_at).toLocaleDateString('ja-JP')
                          : '-'
                        }
                      </Typography>
                      <Typography variant="caption">
                        更新: {new Date(match.updated_at).toLocaleDateString('ja-JP')}
                      </Typography>
                    </Box>
                  </CardContent>
                  <CardActions sx={{ pt: 0, px: 2, pb: 2 }}>
                    <Button
                      size="small"
                      variant="contained"
                      startIcon={<Visibility />}
                      onClick={() => navigate(`/matching/${match.id}`)}
                      fullWidth
                    >
                      詳細を見る
                    </Button>
                  </CardActions>
                </Card>
              ))}
            </Stack>
          )}
        </Box>
      ) : (
        // デスクトップ: テーブル表示
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>物件名</TableCell>
                <TableCell>顧客名</TableCell>
                <TableCell>マッチスコア</TableCell>
                <TableCell>ステータス</TableCell>
                <TableCell>物件価格</TableCell>
                <TableCell>顧客予算</TableCell>
                <TableCell>提案日</TableCell>
                <TableCell>更新日</TableCell>
                <TableCell align="center">操作</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={9} align="center">
                    読み込み中...
                  </TableCell>
                </TableRow>
              ) : matchesData?.data?.data?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} align="center">
                    マッチングが見つかりませんでした
                  </TableCell>
                </TableRow>
              ) : (
                matchesData?.data?.data?.map((match: PropertyMatch) => (
                  <TableRow key={match.id} hover>
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {match.property?.property_name || `物件ID: ${match.property_id}`}
                      </Typography>
                      <Typography variant="caption" color="textSecondary">
                        {match.property?.prefecture} {match.property?.city}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {match.customer?.customer_name || `顧客ID: ${match.customer_id}`}
                      </Typography>
                      <Typography variant="caption" color="textSecondary">
                        {match.customer?.customer_type}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Chip 
                          label={`${match.match_score}%`}
                          size="small"
                          color={getScoreColor(match.match_score) as any}
                          icon={<TrendingUp />}
                        />
                        <Box sx={{ width: 60 }}>
                          <LinearProgress 
                            variant="determinate" 
                            value={match.match_score} 
                            color={getScoreColor(match.match_score) as any}
                          />
                        </Box>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Chip 
                        label={getStatusLabel(match.status)} 
                        size="small"
                        color={getStatusColor(match.status) as any}
                      />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {formatPrice(match.property?.price)}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {match.customer?.budget_min && match.customer?.budget_max 
                          ? `${formatPrice(match.customer.budget_min)} ～ ${formatPrice(match.customer.budget_max)}`
                          : match.customer?.budget_min 
                            ? `${formatPrice(match.customer.budget_min)}以上`
                            : match.customer?.budget_max
                              ? `${formatPrice(match.customer.budget_max)}以下`
                              : '-'
                        }
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {match.presented_at 
                          ? new Date(match.presented_at).toLocaleDateString('ja-JP')
                          : '-'
                        }
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {new Date(match.updated_at).toLocaleDateString('ja-JP')}
                      </Typography>
                    </TableCell>
                    <TableCell align="center">
                      <Button
                        size="small"
                        variant="outlined"
                        onClick={() => navigate(`/matching/${match.id}`)}
                      >
                        詳細
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      {/* ページネーション */}
      {matchesData?.data && (
        <Box sx={{ 
          mt: 2, 
          display: 'flex', 
          justifyContent: 'space-between', 
          alignItems: 'center',
          flexDirection: isMobile ? 'column' : 'row',
          gap: isMobile ? 2 : 0
        }}>
          <Typography variant="body2" color="textSecondary">
            {matchesData.data.from}-{matchesData.data.to} / {matchesData.data.total}件
          </Typography>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Button
              variant="outlined"
              size="small"
              disabled={!matchesData.data.prev_page_url}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              前へ
            </Button>
            <Typography variant="body2" color="textSecondary">
              ページ {matchesData.data.current_page} / {matchesData.data.last_page}
            </Typography>
            <Button
              variant="outlined"
              size="small"
              disabled={!matchesData.data.next_page_url}
              onClick={() => setPage((p) => p + 1)}
            >
              次へ
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default MatchingList; 