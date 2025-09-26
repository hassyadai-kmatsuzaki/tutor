import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Grid,
  Chip,
  Button,
  LinearProgress,
  Divider,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  Paper,
  IconButton,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {
  ArrowBack as ArrowBackIcon,
  Timeline as TimelineIcon,
  Note as NoteIcon,
  Edit as EditIcon,
  Home as HomeIcon,
  Person as PersonIcon,
  TrendingUp as TrendingUpIcon,
  CalendarToday as CalendarIcon,
  LocationOn as LocationIcon,
  AttachMoney as MoneyIcon,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { matchApi } from '../../services/api';
import LoadingSpinner from '../../components/Common/LoadingSpinner';
import ErrorAlert from '../../components/Common/ErrorAlert';

const MatchingDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [showStatusDialog, setShowStatusDialog] = useState(false);
  const [showNoteDialog, setShowNoteDialog] = useState(false);
  const [newStatus, setNewStatus] = useState('');
  const [statusNotes, setStatusNotes] = useState('');
  const [newNote, setNewNote] = useState('');

  const { data: matchData, isLoading, error } = useQuery({
    queryKey: ['match-detail', id],
    queryFn: () => matchApi.getById(Number(id)),
    select: (response) => response.data.data,
  });

  const updateStatusMutation = useMutation({
    mutationFn: ({ status, notes }: { status: string; notes?: string }) =>
      matchApi.updateStatus(Number(id), status, notes),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['match-detail', id] });
      queryClient.invalidateQueries({ queryKey: ['matches'] });
      setShowStatusDialog(false);
      setNewStatus('');
      setStatusNotes('');
    },
  });

  const addNoteMutation = useMutation({
    mutationFn: (note: string) => matchApi.addNote(Number(id), note),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['match-detail', id] });
      setShowNoteDialog(false);
      setNewNote('');
    },
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'matched': return 'primary';
      case 'reviewed': return 'info';
      case 'presented': return 'warning';
      case 'interested': return 'success';
      case 'not_interested': return 'error';
      case 'contracted': return 'success';
      case 'expired': return 'default';
      default: return 'default';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'matched': return 'マッチング';
      case 'reviewed': return '確認済み';
      case 'presented': return '提案済み';
      case 'interested': return '興味あり';
      case 'not_interested': return '興味なし';
      case 'contracted': return '契約成立';
      case 'expired': return '期限切れ';
      default: return status;
    }
  };

  const handleStatusUpdate = () => {
    updateStatusMutation.mutate({
      status: newStatus,
      notes: statusNotes || undefined,
    });
  };

  const handleAddNote = () => {
    if (newNote.trim()) {
      addNoteMutation.mutate(newNote.trim());
    }
  };

  if (isLoading) return <LoadingSpinner message="マッチング情報を読み込み中..." />;
  if (error) return <ErrorAlert title="エラー" message="マッチング情報の取得に失敗しました" />;
  if (!matchData) return <ErrorAlert title="エラー" message="マッチング情報が見つかりません" />;

  return (
    <Box sx={{ p: { xs: 1.5, md: 3 } }}>
      {/* ヘッダー */}
      <Box sx={{ display: 'flex', alignItems: isMobile ? 'flex-start' : 'center', mb: isMobile ? 2 : 3, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 1 : 0 }}>
        <IconButton onClick={() => navigate('/matching')} sx={{ mr: isMobile ? 0 : 2 }}>
          <ArrowBackIcon />
        </IconButton>
        <Typography variant={isMobile ? 'h5' : 'h4'} sx={{ flexGrow: 1 }}>
          マッチング詳細
        </Typography>
        <Box sx={{ display: 'flex', gap: 1, width: isMobile ? '100%' : 'auto' }}>
          <Button
            variant="outlined"
            startIcon={<EditIcon />}
            onClick={() => setShowStatusDialog(true)}
            size={isMobile ? 'small' : 'medium'}
            fullWidth={isMobile}
          >
            ステータス更新
          </Button>
          <Button
            variant="outlined"
            startIcon={<NoteIcon />}
            onClick={() => setShowNoteDialog(true)}
            size={isMobile ? 'small' : 'medium'}
            fullWidth={isMobile}
          >
            メモ追加
          </Button>
        </Box>
      </Box>

      <Grid container spacing={isMobile ? 1.5 : 3}>
        {/* マッチングスコア */}
        <Grid item xs={12}>
          <Card>
            <CardContent sx={{ p: isMobile ? 1.5 : 2 }}>
              <Typography variant={isMobile ? 'subtitle1' : 'h6'} gutterBottom>
                マッチングスコア
              </Typography>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: isMobile ? 1.5 : 2 }}>
                <Typography variant={isMobile ? 'h4' : 'h3'} color="primary" sx={{ mr: 2 }}>
                  {matchData.score_details?.total_score || matchData.match_score}
                </Typography>
                <Typography variant={isMobile ? 'subtitle1' : 'h6'} color="text.secondary">
                  / 100
                </Typography>
                <Chip
                  label={getStatusLabel(matchData.status)}
                  color={getStatusColor(matchData.status) as any}
                  sx={{ ml: 'auto' }}
                />
              </Box>

              {matchData.score_details && (
                <Grid container spacing={isMobile ? 1.5 : 2}>
                  <Grid item xs={12} md={2.4}>
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        予算適合度 ({matchData.score_details.weights.budget}%)
                      </Typography>
                      <LinearProgress
                        variant="determinate"
                        value={matchData.score_details.budget_score}
                        sx={{ mt: 0.5, mb: 0.5, height: isMobile ? 6 : 8, borderRadius: 3 }}
                      />
                      <Typography variant="body2" fontWeight="bold">
                        {matchData.score_details.budget_score}点
                      </Typography>
                    </Box>
                  </Grid>
                  <Grid item xs={12} md={2.4}>
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        エリア適合度 ({matchData.score_details.weights.area}%)
                      </Typography>
                      <LinearProgress
                        variant="determinate"
                        value={matchData.score_details.area_score}
                        sx={{ mt: 0.5, mb: 0.5, height: isMobile ? 6 : 8, borderRadius: 3 }}
                      />
                      <Typography variant="body2" fontWeight="bold">
                        {matchData.score_details.area_score}点
                      </Typography>
                    </Box>
                  </Grid>
                  <Grid item xs={12} md={2.4}>
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        タイプ適合度 ({matchData.score_details.weights.type}%)
                      </Typography>
                      <LinearProgress
                        variant="determinate"
                        value={matchData.score_details.type_score}
                        sx={{ mt: 0.5, mb: 0.5, height: isMobile ? 6 : 8, borderRadius: 3 }}
                      />
                      <Typography variant="body2" fontWeight="bold">
                        {matchData.score_details.type_score}点
                      </Typography>
                    </Box>
                  </Grid>
                  <Grid item xs={12} md={2.4}>
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        面積適合度 ({matchData.score_details.weights.size}%)
                      </Typography>
                      <LinearProgress
                        variant="determinate"
                        value={matchData.score_details.size_score}
                        sx={{ mt: 0.5, mb: 0.5, height: isMobile ? 6 : 8, borderRadius: 3 }}
                      />
                      <Typography variant="body2" fontWeight="bold">
                        {matchData.score_details.size_score}点
                      </Typography>
                    </Box>
                  </Grid>
                  <Grid item xs={12} md={2.4}>
                    <Box>
                      <Typography variant="body2" color="text.secondary">
                        利回り適合度 ({matchData.score_details.weights.yield}%)
                      </Typography>
                      <LinearProgress
                        variant="determinate"
                        value={matchData.score_details.yield_score}
                        sx={{ mt: 0.5, mb: 0.5, height: isMobile ? 6 : 8, borderRadius: 3 }}
                      />
                      <Typography variant="body2" fontWeight="bold">
                        {matchData.score_details.yield_score}点
                      </Typography>
                    </Box>
                  </Grid>
                </Grid>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* 物件情報 */}
        <Grid item xs={12} md={6}>
          <Card sx={{ height: '100%' }}>
            <CardContent sx={{ p: isMobile ? 1.5 : 2 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: isMobile ? 1.5 : 2 }}>
                <HomeIcon sx={{ mr: 1 }} />
                <Typography variant={isMobile ? 'subtitle1' : 'h6'}>物件情報</Typography>
              </Box>
              <Typography variant={isMobile ? 'h6' : 'h5'} gutterBottom>
                {matchData.property.property_name}
              </Typography>
              <List dense>
                <ListItem>
                  <ListItemIcon><LocationIcon /></ListItemIcon>
                  <ListItemText
                    primary="所在地"
                    secondary={`${matchData.property.prefecture} ${matchData.property.city}`}
                  />
                </ListItem>
                <ListItem>
                  <ListItemIcon><MoneyIcon /></ListItemIcon>
                  <ListItemText
                    primary="価格"
                    secondary={`${(matchData.property.price / 10000).toLocaleString()}万円`}
                  />
                </ListItem>
                <ListItem>
                  <ListItemIcon><TrendingUpIcon /></ListItemIcon>
                  <ListItemText
                    primary="利回り"
                    secondary={matchData.property.current_profit ? `${matchData.property.current_profit}%` : '未設定'}
                  />
                </ListItem>
              </List>
              <Button
                variant="outlined"
                size={isMobile ? 'small' : 'medium'}
                onClick={() => navigate(`/properties/${matchData.property.id}`)}
                sx={{ mt: isMobile ? 1.5 : 2 }}
              >
                物件詳細を見る
              </Button>
            </CardContent>
          </Card>
        </Grid>

        {/* 顧客情報 */}
        <Grid item xs={12} md={6}>
          <Card sx={{ height: '100%' }}>
            <CardContent sx={{ p: isMobile ? 1.5 : 2 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: isMobile ? 1.5 : 2 }}>
                <PersonIcon sx={{ mr: 1 }} />
                <Typography variant={isMobile ? 'subtitle1' : 'h6'}>顧客情報</Typography>
              </Box>
              <Typography variant={isMobile ? 'h6' : 'h5'} gutterBottom>
                {matchData.customer.customer_name}
              </Typography>
              <List dense>
                <ListItem>
                  <ListItemText
                    primary="予算"
                    secondary={`${(matchData.customer.budget_min / 10000).toLocaleString()}万円 〜 ${(matchData.customer.budget_max / 10000).toLocaleString()}万円`}
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="希望エリア"
                    secondary={matchData.customer.area_preference || '未設定'}
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="優先度"
                    secondary={matchData.customer.priority}
                  />
                </ListItem>
              </List>
              <Button
                variant="outlined"
                size={isMobile ? 'small' : 'medium'}
                onClick={() => navigate(`/customers/${matchData.customer.id}`)}
                sx={{ mt: isMobile ? 1.5 : 2 }}
              >
                顧客詳細を見る
              </Button>
            </CardContent>
          </Card>
        </Grid>

        {/* ステータス履歴 */}
        {matchData.status_history && matchData.status_history.length > 0 && (
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent sx={{ p: isMobile ? 1.5 : 2 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: isMobile ? 1.5 : 2 }}>
                  <TimelineIcon sx={{ mr: 1 }} />
                  <Typography variant={isMobile ? 'subtitle1' : 'h6'}>ステータス履歴</Typography>
                </Box>
                <List dense>
                  {matchData.status_history.map((history: any, index: number) => (
                    <ListItem key={index}>
                      <ListItemIcon>
                        <CalendarIcon fontSize="small" />
                      </ListItemIcon>
                      <ListItemText
                        primary={getStatusLabel(history.status)}
                        secondary={`${new Date(history.date).toLocaleDateString('ja-JP')} - ${history.user}`}
                      />
                    </ListItem>
                  ))}
                </List>
              </CardContent>
            </Card>
          </Grid>
        )}

        {/* メモ・活動履歴 */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent sx={{ p: isMobile ? 1.5 : 2 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: isMobile ? 1.5 : 2 }}>
                <NoteIcon sx={{ mr: 1 }} />
                <Typography variant={isMobile ? 'subtitle1' : 'h6'}>メモ・活動履歴</Typography>
              </Box>
              {matchData.notes ? (
                <Paper sx={{ p: isMobile ? 1.5 : 2, mb: isMobile ? 1.5 : 2, bgcolor: 'grey.50' }}>
                  <Typography variant="body2" component="pre" sx={{ whiteSpace: 'pre-wrap' }}>
                    {matchData.notes}
                  </Typography>
                </Paper>
              ) : (
                <Typography variant="body2" color="text.secondary" sx={{ mb: isMobile ? 1.5 : 2 }}>
                  メモはありません
                </Typography>
              )}
              
              {matchData.activities && matchData.activities.length > 0 && (
                <Box>
                  <Divider sx={{ my: isMobile ? 1.5 : 2 }} />
                  <Typography variant="subtitle2" gutterBottom>
                    最近の活動
                  </Typography>
                  <List dense>
                    {matchData.activities.slice(0, 5).map((activity: any) => (
                      <ListItem key={activity.id}>
                        <ListItemText
                          primary={activity.description}
                          secondary={`${new Date(activity.created_at).toLocaleDateString('ja-JP')} - ${activity.user?.name || 'システム'}`}
                        />
                      </ListItem>
                    ))}
                  </List>
                </Box>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* ステータス更新ダイアログ */}
      <Dialog open={showStatusDialog} onClose={() => setShowStatusDialog(false)} maxWidth="sm" fullWidth>
        <DialogTitle>ステータス更新</DialogTitle>
        <DialogContent>
          <FormControl fullWidth sx={{ mt: 2, mb: 2 }}>
            <InputLabel>新しいステータス</InputLabel>
            <Select
              value={newStatus}
              label="新しいステータス"
              onChange={(e) => setNewStatus(e.target.value)}
            >
              <MenuItem value="matched">マッチング</MenuItem>
              <MenuItem value="reviewed">確認済み</MenuItem>
              <MenuItem value="presented">提案済み</MenuItem>
              <MenuItem value="interested">興味あり</MenuItem>
              <MenuItem value="not_interested">興味なし</MenuItem>
              <MenuItem value="contracted">契約成立</MenuItem>
              <MenuItem value="expired">期限切れ</MenuItem>
            </Select>
          </FormControl>
          <TextField
            fullWidth
            multiline
            rows={3}
            label="メモ（任意）"
            value={statusNotes}
            onChange={(e) => setStatusNotes(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setShowStatusDialog(false)}>キャンセル</Button>
          <Button
            onClick={handleStatusUpdate}
            variant="contained"
            disabled={!newStatus || updateStatusMutation.isPending}
          >
            更新
          </Button>
        </DialogActions>
      </Dialog>

      {/* メモ追加ダイアログ */}
      <Dialog open={showNoteDialog} onClose={() => setShowNoteDialog(false)} maxWidth="sm" fullWidth>
        <DialogTitle>メモ追加</DialogTitle>
        <DialogContent>
          <TextField
            fullWidth
            multiline
            rows={4}
            label="メモ"
            value={newNote}
            onChange={(e) => setNewNote(e.target.value)}
            sx={{ mt: 2 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setShowNoteDialog(false)}>キャンセル</Button>
          <Button
            onClick={handleAddNote}
            variant="contained"
            disabled={!newNote.trim() || addNoteMutation.isPending}
          >
            追加
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default MatchingDetail; 