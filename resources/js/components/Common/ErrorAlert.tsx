import React from 'react';
import { Alert, AlertTitle, Box } from '@mui/material';

interface ErrorAlertProps {
  title?: string;
  message: string;
  onRetry?: () => void;
}

const ErrorAlert: React.FC<ErrorAlertProps> = ({ 
  title = 'エラーが発生しました', 
  message,
  onRetry 
}) => {
  return (
    <Box sx={{ mb: 2 }}>
      <Alert 
        severity="error"
        action={
          onRetry ? (
            <button onClick={onRetry} style={{ background: 'none', border: 'none', color: 'inherit', textDecoration: 'underline', cursor: 'pointer' }}>
              再試行
            </button>
          ) : undefined
        }
      >
        <AlertTitle>{title}</AlertTitle>
        {message}
      </Alert>
    </Box>
  );
};

export default ErrorAlert; 