import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        //tailwindcss(),
    ],
    build: {
        rollupOptions: {
            onwarn(warning, warn) {
                // "use client" ディレクティブ警告を抑制
                if (warning.code === 'MODULE_LEVEL_DIRECTIVE') {
                    return;
                }
                // Material-UIの警告も抑制
                if (warning.message && warning.message.includes('"use client"')) {
                    return;
                }
                warn(warning);
            },
            output: {
                // チャンクサイズ警告を解決するための手動チャンク分割
                manualChunks: {
                    // Material-UI関連を別チャンクに分離
                    'mui-core': ['@mui/material', '@mui/system', '@emotion/react', '@emotion/styled'],
                    'mui-icons': ['@mui/icons-material'],
                    // React関連を別チャンクに分離
                    'react-vendor': ['react', 'react-dom', 'react-router-dom'],
                    // その他のベンダーライブラリ
                    'vendor': ['axios']
                }
            }
        },
        // チャンクサイズ警告の上限を調整
        chunkSizeWarningLimit: 1000
    },
    // 開発時の警告も抑制
    esbuild: {
        logOverride: {
            'this-is-undefined-in-esm': 'silent'
        }
    },
    // その他の設定
    optimizeDeps: {
        include: ['react', 'react-dom', '@mui/material', '@mui/icons-material']
    }
});
