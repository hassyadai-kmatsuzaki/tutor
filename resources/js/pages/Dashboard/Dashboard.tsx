import React from 'react';
import {
  Box,
  Grid,
  Paper,
  Typography,
  Card,
  CardContent,
  Alert,
  Chip,
} from '@mui/material';
import {
  TrendingUp,
  Home,
  People,
  Assessment,
} from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { dashboardApi } from '../../services/api';

const Dashboard: React.FC = () => {
  const { data: statsData, isLoading: statsLoading, error: statsError } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => dashboardApi.getStats(),
    select: (response) => response.data.data,
  });

  const { data: alertsData, error: alertsError } = useQuery({
    queryKey: ['dashboard-alerts'],
    queryFn: () => dashboardApi.getAlerts(),
    select: (response) => response.data.data,
  });

  if (statsLoading) {
    return (
      <Box>
        <Typography>読み込み中...</Typography>
      </Box>
    );
  }

  if (statsError || alertsError) {
    return (
      <Box>
        <Typography variant="h4" gutterBottom>
          ダッシュボード
        </Typography>
        <Alert severity="error">
          データの読み込みに失敗しました。APIサーバーが起動していることを確認してください。
        </Alert>
      </Box>
    );
  }

  const stats = statsData?.overview || {
    total_properties: 0,
    available_properties: 0,
    total_customers: 0,
    active_customers: 0,
    total_matches: 0,
    high_score_matches: 0,
    contracts_this_month: 0,
  };

  return (
    <Box sx={{ flexGrow: 1 }}>
      <Typography variant="h4" gutterBottom>
        ダッシュボード
      </Typography>

      {/* アラート */}
      {alertsData && alertsData.length > 0 && (
        <Box sx={{ mb: 3 }}>
          {alertsData.map((alert: any, index: number) => (
            <Alert
              key={index}
              severity={alert.type}
              sx={{ mb: 1 }}
              action={
                <Chip
                  label={alert.count}
                  size="small"
                  color={alert.type === 'warning' ? 'warning' : 'default'}
                />
              }
            >
              <strong>{alert.title}</strong>: {alert.message}
            </Alert>
          ))}
        </Box>
      )}

      {/* 概要統計 */}
      <Grid container spacing={3} sx={{ mb: 3 }}>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center' }}>
                <Home sx={{ fontSize: 40, color: 'primary.main', mr: 2 }} />
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    登録物件数
                  </Typography>
                  <Typography variant="h5">
                    {stats.total_properties || 0}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    販売中: {stats.available_properties || 0}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center' }}>
                <People sx={{ fontSize: 40, color: 'secondary.main', mr: 2 }} />
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    登録顧客数
                  </Typography>
                  <Typography variant="h5">
                    {stats.total_customers || 0}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    アクティブ: {stats.active_customers || 0}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center' }}>
                <TrendingUp sx={{ fontSize: 40, color: 'success.main', mr: 2 }} />
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    マッチング数
                  </Typography>
                  <Typography variant="h5">
                    {stats.total_matches || 0}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    高スコア: {stats.high_score_matches || 0}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center' }}>
                <Assessment sx={{ fontSize: 40, color: 'warning.main', mr: 2 }} />
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    今月の成約
                  </Typography>
                  <Typography variant="h5">
                    {stats.contracts_this_month || 0}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    件
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* 詳細統計 */}
      <Grid container spacing={3}>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              物件ステータス別
            </Typography>
            {statsData?.properties?.by_status && (
              <Box>
                {Object.entries(statsData.properties.by_status).map(([status, count]) => (
                  <Box key={status} sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography>{status}</Typography>
                    <Typography fontWeight="bold">{count as number}</Typography>
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>

        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              顧客ステータス別
            </Typography>
            {statsData?.customers?.by_status && (
              <Box>
                {Object.entries(statsData.customers.by_status).map(([status, count]) => (
                  <Box key={status} sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography>{status}</Typography>
                    <Typography fontWeight="bold">{count as number}</Typography>
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>

        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              物件種別別
            </Typography>
            {statsData?.properties?.by_type && (
              <Box>
                {Object.entries(statsData.properties.by_type).map(([type, count]) => (
                  <Box key={type} sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography>{type}</Typography>
                    <Typography fontWeight="bold">{count as number}</Typography>
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>

        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              マッチングステータス別
            </Typography>
            {statsData?.matches?.by_status && (
              <Box>
                {Object.entries(statsData.matches.by_status).map(([status, count]) => (
                  <Box key={status} sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
                    <Typography>{status}</Typography>
                    <Typography fontWeight="bold">{count as number}</Typography>
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
};

export default Dashboard; 