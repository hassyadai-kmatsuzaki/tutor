import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '../contexts/AuthContext';
import ProtectedRoute from '../components/Auth/ProtectedRoute';
import Layout from '../components/Layout/Layout';
import Login from '../pages/Auth/Login';

// 実装済みのページコンポーネントをインポート
import Dashboard from '../pages/Dashboard/Dashboard';
import PropertyList from '../pages/Properties/PropertyList';
import PropertyDetail from '../pages/Properties/PropertyDetail';
import CustomerList from '../pages/Customers/CustomerList';
import CustomerDetail from '../pages/Customers/CustomerDetail';
import MatchingList from '../pages/Matching/MatchingList';
import MatchingDetail from '../pages/Matching/MatchingDetail';
import UserManagement from '../pages/Admin/UserManagement';

// メインAppコンポーネント
const App: React.FC = () => {
  console.log('App component rendering...'); // デバッグ用
  return (
    <AuthProvider>
      <Routes>
        {/* 認証不要のルート */}
        <Route path="/login" element={<Login />} />
        
        {/* 認証が必要なルート */}
        <Route path="/*" element={
          <ProtectedRoute>
            <Layout>
              <Routes>
                <Route path="/" element={<Navigate to="/dashboard" replace />} />
                <Route path="/dashboard" element={<Dashboard />} />
                <Route path="/properties" element={<PropertyList />} />
                <Route path="/properties/:id" element={<PropertyDetail />} />
                <Route path="/customers" element={<CustomerList />} />
                <Route path="/customers/:id" element={<CustomerDetail />} />
                <Route path="/matching" element={<MatchingList />} />
                <Route path="/matching/:id" element={<MatchingDetail />} />
                <Route path="/admin/users" element={<UserManagement />} />
              </Routes>
            </Layout>
          </ProtectedRoute>
        } />
      </Routes>
    </AuthProvider>
  );
};

export default App;
