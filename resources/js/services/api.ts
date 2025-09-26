import axios, { AxiosResponse } from 'axios';
import {
  Property,
  Customer,
  PropertyMatch,
  Activity,
  DashboardStats,
  ApiResponse,
  PaginatedResponse,
  PropertyFilters,
  CustomerFilters,
  MatchFilters,
  PropertyFormData,
  CustomerFormData,
} from '../types';

// CSRFトークンを取得
const getCSRFToken = (): string | null => {
  const token = document.head.querySelector('meta[name="csrf-token"]');
  return token ? (token as HTMLMetaElement).content : null;
};

// Axios インスタンスの作成
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// リクエストインターセプターでトークンを付与
api.interceptors.request.use((config) => {
  // CSRFトークンを設定
  const csrfToken = getCSRFToken();
  if (csrfToken) {
    config.headers['X-CSRF-TOKEN'] = csrfToken;
  }

  // 認証トークンを設定
  const authToken = localStorage.getItem('auth_token');
  if (authToken) {
    config.headers.Authorization = `Bearer ${authToken}`;
  }
  
  return config;
});

// レスポンスインターセプターで認証エラーをハンドル
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);





// ダッシュボード API
export const dashboardApi = {
  getStats: (period?: string): Promise<AxiosResponse<ApiResponse<DashboardStats>>> =>
    api.get('/dashboard/stats', { params: { period } }),
  
  getActivities: (limit?: number): Promise<AxiosResponse<ApiResponse<Activity[]>>> =>
    api.get('/dashboard/activities', { params: { limit } }),
  
  getAlerts: (): Promise<AxiosResponse<ApiResponse<any[]>>> =>
    api.get('/dashboard/alerts'),
  
  getSalesAnalysis: (period?: string): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/dashboard/sales-analysis', { params: { period } }),
};

// 物件管理 API
export const propertyApi = {
  getList: (filters?: PropertyFilters): Promise<AxiosResponse<PaginatedResponse<Property>>> =>
    api.get('/properties', { params: filters }),
  
  getById: (id: number): Promise<AxiosResponse<ApiResponse<Property>>> =>
    api.get(`/properties/${id}`),
  
  create: (data: PropertyFormData): Promise<AxiosResponse<ApiResponse<Property>>> =>
    api.post('/properties', data),
  
  update: (id: number, data: Partial<PropertyFormData>): Promise<AxiosResponse<ApiResponse<Property>>> =>
    api.put(`/properties/${id}`, data),
  
  delete: (id: number): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.delete(`/properties/${id}`),
  
  getImages: (id: number): Promise<AxiosResponse<ApiResponse<any[]>>> =>
    api.get(`/properties/${id}/images`),
  
  uploadImage: (id: number, formData: FormData): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.post(`/properties/${id}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
  
  deleteImage: (id: number, imageId: number): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.delete(`/properties/${id}/images/${imageId}`),
  
  getStatistics: (): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/properties/statistics'),
  
  import: (file: File): Promise<AxiosResponse<ApiResponse<any>>> => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/properties/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};

// 顧客管理 API
export const customerApi = {
  getList: (filters?: CustomerFilters): Promise<AxiosResponse<PaginatedResponse<Customer>>> =>
    api.get('/customers', { params: filters }),
  
  getById: (id: number): Promise<AxiosResponse<ApiResponse<Customer>>> =>
    api.get(`/customers/${id}`),
  
  create: (data: CustomerFormData): Promise<AxiosResponse<ApiResponse<Customer>>> =>
    api.post('/customers', data),
  
  update: (id: number, data: Partial<CustomerFormData>): Promise<AxiosResponse<ApiResponse<Customer>>> =>
    api.put(`/customers/${id}`, data),
  
  delete: (id: number): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.delete(`/customers/${id}`),
  
  getPreferences: (id: number): Promise<AxiosResponse<ApiResponse<any[]>>> =>
    api.get(`/customers/${id}/preferences`),
  
  updatePreferences: (id: number, preferences: any[]): Promise<AxiosResponse<ApiResponse<any[]>>> =>
    api.put(`/customers/${id}/preferences`, { preferences }),
  
  getActivities: (id: number): Promise<AxiosResponse<PaginatedResponse<Activity>>> =>
    api.get(`/customers/${id}/activities`),
  
  addActivity: (id: number, activity: any): Promise<AxiosResponse<ApiResponse<Activity>>> =>
    api.post(`/customers/${id}/activities`, activity),
  
  recordContact: (id: number, contact: any): Promise<AxiosResponse<ApiResponse<Customer>>> =>
    api.post(`/customers/${id}/contact`, contact),
  
  getStatistics: (): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/customers/statistics'),
  
  getByAssignee: (assigneeId?: number): Promise<AxiosResponse<ApiResponse<Customer[]>>> =>
    api.get('/customers/by-assignee', { params: { assignee_id: assigneeId } }),

  import: (file: File): Promise<AxiosResponse<ApiResponse<any>>> => {
    const formData = new FormData();
    formData.append('file', file);
    return api.post('/customers/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
};

// マッチング管理 API
export const matchApi = {
  getList: (filters?: MatchFilters): Promise<AxiosResponse<PaginatedResponse<PropertyMatch>>> =>
    api.get('/matches', { params: filters }),
  
  getById: (id: number): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.get(`/matches/${id}`),
  
  generate: (params?: {
    property_id?: number;
    customer_id?: number;
    min_score?: number;
  }): Promise<AxiosResponse<ApiResponse<{ created_matches: number }>>> =>
    api.post('/matches/generate', params),
  
  update: (id: number, data: {
    status: string;
    response_comment?: string;
  }): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.put(`/matches/${id}`, data),
  
  updateStatus: (id: number, status: string, notes?: string): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.put(`/matches/${id}/status`, { status, notes }),
  
  addNote: (id: number, note: string): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.post(`/matches/${id}/notes`, { note }),
  
  delete: (id: number): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.delete(`/matches/${id}`),
  
  present: (id: number, comment?: string): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.post(`/matches/${id}/present`, { comment }),
  
  recordResponse: (id: number, data: {
    status: string;
    comment?: string;
  }): Promise<AxiosResponse<ApiResponse<PropertyMatch>>> =>
    api.post(`/matches/${id}/response`, data),
  
  getStatistics: (): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/matches/statistics'),
};

// 推奨機能 API
export const recommendationApi = {
  getRecommendedCustomers: (propertyId: number): Promise<AxiosResponse<ApiResponse<PropertyMatch[]>>> =>
    api.get(`/recommendations/properties/${propertyId}/customers`),
  
  getRecommendedProperties: (customerId: number): Promise<AxiosResponse<ApiResponse<PropertyMatch[]>>> =>
    api.get(`/recommendations/customers/${customerId}/properties`),
};

// 認証 API
export const authApi = {
  login: (email: string, password: string): Promise<AxiosResponse<ApiResponse<{ user: any; token: string }>>> =>
    api.post('/login', { email, password }),
  
  logout: (): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.post('/logout'),
  
  me: (): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/me'),
};

// ユーザー管理 API（管理者のみ）
export const userApi = {
  getList: (filters?: any): Promise<AxiosResponse<PaginatedResponse<any>>> =>
    api.get('/users', { params: filters }),
  
  getById: (id: number): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get(`/users/${id}`),
  
  create: (data: any): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.post('/users', data),
  
  update: (id: number, data: any): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.put(`/users/${id}`, data),
  
  delete: (id: number): Promise<AxiosResponse<ApiResponse<void>>> =>
    api.delete(`/users/${id}`),
  
  getStatistics: (): Promise<AxiosResponse<ApiResponse<any>>> =>
    api.get('/users/statistics'),
};

export default api; 