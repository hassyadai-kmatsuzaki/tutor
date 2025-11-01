<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>物件検索 - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=noto-sans-jp:400,500,700" rel="stylesheet" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans JP', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
        }

        /* モバイルでのヘッダーを大きく */
        @media (max-width: 767px) {
            .header {
                padding: 1.25rem 1rem;
            }

            .header h1 {
                font-size: 1.375rem;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        .search-section {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* モバイルでの余白を調整 */
        @media (max-width: 767px) {
            .search-section {
                padding: 1.5rem;
            }
        }

        .search-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #555;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            -webkit-appearance: none;
            appearance: none;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            transform: scale(1.01);
        }

        /* モバイルでのタップターゲットサイズを確保 */
        @media (max-width: 767px) {
            .form-input,
            .form-select {
                padding: 1.125rem;
                font-size: 1.0625rem;
            }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        @media (min-width: 640px) {
            .checkbox-group {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 768px) {
            .checkbox-group {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .checkbox-label:active {
            background: rgba(102, 126, 234, 0.1);
        }

        .checkbox-label input {
            margin-right: 0.5rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* モバイルでのタップ領域を拡大 */
        @media (max-width: 767px) {
            .checkbox-label {
                padding: 0.75rem;
                font-size: 0.9375rem;
            }
            
            .checkbox-label input {
                width: 22px;
                height: 22px;
            }
        }

        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            touch-action: manipulation;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }

        @media (min-width: 768px) {
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
            }
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
            margin-top: 0.75rem;
        }

        .btn-secondary:active {
            background: #e0e0e0;
            transform: scale(0.98);
        }

        @media (min-width: 768px) {
            .btn-secondary:hover {
                background: #e0e0e0;
            }
        }

        /* モバイルでのボタンサイズを拡大 */
        @media (max-width: 767px) {
            .btn {
                padding: 1.25rem 1.5rem;
                font-size: 1.0625rem;
            }
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .results-count {
            font-size: 0.875rem;
            color: #666;
        }

        .sort-select {
            padding: 0.625rem 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
        }

        /* モバイルでのソート選択を大きく */
        @media (max-width: 767px) {
            .sort-select {
                padding: 0.75rem 1rem;
                font-size: 0.9375rem;
                width: 100%;
                margin-top: 0.5rem;
            }

            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        .properties-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .properties-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .properties-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .property-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .property-card:active {
            transform: scale(0.98);
            border-color: #667eea;
        }

        @media (min-width: 768px) {
            .property-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.15);
                border-color: #667eea;
            }
        }

        .property-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #667eea;
            color: white;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .property-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .property-address {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 0.75rem;
        }

        .property-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .property-price {
            grid-column: 1 / -1;
            font-size: 1.25rem;
            font-weight: 700;
            color: #667eea;
            margin-top: 0.5rem;
        }

        .property-detail-item {
            display: flex;
            flex-direction: column;
        }

        .property-detail-label {
            font-size: 0.75rem;
            color: #999;
        }

        .property-detail-value {
            font-weight: 600;
            color: #333;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination-btn {
            padding: 0.75rem 1rem;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            touch-action: manipulation;
            font-size: 0.9375rem;
            min-width: 44px;
        }

        .pagination-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        @media (min-width: 768px) {
            .pagination-btn:hover:not(:disabled) {
                background: #667eea;
                color: white;
                border-color: #667eea;
            }
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* モバイルでのページネーションボタンを大きく */
        @media (max-width: 767px) {
            .pagination-btn {
                padding: 0.875rem 1.125rem;
                font-size: 1rem;
            }
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            margin: 2rem 0;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: white;
            border: none;
            font-size: 1.5rem;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 10;
            touch-action: manipulation;
        }

        .modal-close:active {
            background: #e0e0e0;
            transform: scale(0.95);
        }

        @media (min-width: 768px) {
            .modal-close:hover {
                background: #f0f0f0;
            }
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-property-type {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .modal-property-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }

        .modal-property-price {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1.5rem;
        }

        .modal-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 640px) {
            .modal-detail-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .modal-detail-item {
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .modal-detail-label {
            font-size: 0.875rem;
            color: #999;
            margin-bottom: 0.25rem;
        }

        .modal-detail-value {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .modal-remarks {
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .modal-remarks-label {
            font-size: 0.875rem;
            color: #999;
            margin-bottom: 0.5rem;
        }

        .modal-remarks-text {
            white-space: pre-wrap;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 物件検索</h1>
    </div>

    <div class="container">
        <!-- 検索フォーム -->
        <div class="search-section">
            <div class="search-title">検索条件</div>
            
            <div class="form-group">
                <label class="form-label">キーワード</label>
                <input type="text" class="form-input" id="keyword" placeholder="物件名、住所など">
            </div>

            <div class="form-group">
                <label class="form-label">物件種別</label>
                <div class="checkbox-group" id="property-types">
                    <!-- 動的に生成 -->
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">取引形態</label>
                <input type="text" class="form-input" id="transaction_category" placeholder="売主、元付、先物など">
            </div>

            <div class="form-group">
                <label class="form-label">都道府県</label>
                <input type="text" class="form-input" id="prefecture" placeholder="東京都、大阪府など">
            </div>

            <div class="form-group">
                <label class="form-label">市区町村</label>
                <input type="text" class="form-input" id="city" placeholder="渋谷区、大阪市など">
            </div>

            <div class="form-group">
                <label class="form-label">価格（万円）</label>
                <div class="form-row">
                    <input type="number" class="form-input" id="price_min" placeholder="下限">
                    <input type="number" class="form-input" id="price_max" placeholder="上限">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">利回り下限（%）</label>
                <input type="number" class="form-input" id="yield_min" placeholder="例：5" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label">築年数（年以内）</label>
                <input type="number" class="form-input" id="building_age" placeholder="例：10 で築10年以内">
            </div>

            <button class="btn btn-primary" onclick="searchProperties()">🔍 検索する</button>
            <button class="btn btn-secondary" onclick="resetSearch()">リセット</button>
        </div>

        <!-- 検索結果 -->
        <div id="results-section">
            <div class="results-header">
                <div class="results-count" id="results-count">検索結果: 0件</div>
                <select class="sort-select" id="sort-select" onchange="searchProperties()">
                    <option value="created_at-desc">新着順</option>
                    <option value="price-asc">価格が安い順</option>
                    <option value="price-desc">価格が高い順</option>
                    <option value="current_profit-desc">利回りが高い順</option>
                    <option value="current_profit-asc">利回りが低い順</option>
                </select>
            </div>

            <div id="properties-container">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>物件を読み込んでいます...</p>
                </div>
            </div>

            <div id="pagination-container" class="pagination"></div>
        </div>
    </div>

    <!-- 物件詳細モーダル -->
    <div class="modal" id="property-modal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let propertyTypes = [];

        // ページ読み込み時に物件を取得
        document.addEventListener('DOMContentLoaded', function() {
            fetchPropertyTypes();
            searchProperties();
        });

        // 物件種別を取得
        async function fetchPropertyTypes() {
            try {
                const response = await fetch('/api/public/properties/search');
                const data = await response.json();
                if (data.success && data.filters) {
                    propertyTypes = data.filters.property_types;
                    renderPropertyTypes();
                }
            } catch (error) {
                console.error('Error fetching property types:', error);
            }
        }

        // 物件種別のチェックボックスを生成
        function renderPropertyTypes() {
            const container = document.getElementById('property-types');
            container.innerHTML = propertyTypes.map(type => `
                <label class="checkbox-label">
                    <input type="checkbox" name="property_type" value="${type}">
                    <span>${type}</span>
                </label>
            `).join('');
        }

        // 物件検索
        async function searchProperties(page = 1) {
            currentPage = page;
            const container = document.getElementById('properties-container');
            container.innerHTML = '<div class="loading"><div class="spinner"></div><p>検索中...</p></div>';

            // 検索条件を取得
            const params = new URLSearchParams();
            
            const keyword = document.getElementById('keyword').value;
            if (keyword) params.append('keyword', keyword);

            const selectedTypes = Array.from(document.querySelectorAll('input[name="property_type"]:checked'))
                .map(cb => cb.value);
            selectedTypes.forEach(type => params.append('property_type[]', type));

            const prefecture = document.getElementById('prefecture').value;
            if (prefecture) params.append('prefecture', prefecture);

            const city = document.getElementById('city').value;
            if (city) params.append('city', city);

            const priceMin = document.getElementById('price_min').value;
            if (priceMin) params.append('price_min', priceMin);

            const priceMax = document.getElementById('price_max').value;
            if (priceMax) params.append('price_max', priceMax);

            const yieldMin = document.getElementById('yield_min').value;
            if (yieldMin) params.append('yield_min', yieldMin);

            const transactionCategory = document.getElementById('transaction_category').value;
            if (transactionCategory) params.append('transaction_category', transactionCategory);

            const buildingAge = document.getElementById('building_age').value;
            if (buildingAge) params.append('building_age', buildingAge);

            const sortValue = document.getElementById('sort-select').value.split('-');
            params.append('sort_by', sortValue[0]);
            params.append('sort_order', sortValue[1]);

            params.append('page', page);

            try {
                const response = await fetch(`/api/public/properties/search?${params.toString()}`);
                const data = await response.json();

                if (data.success) {
                    renderProperties(data.data);
                    renderPagination(data.data);
                }
            } catch (error) {
                console.error('Error searching properties:', error);
                container.innerHTML = '<div class="no-results"><p>エラーが発生しました。もう一度お試しください。</p></div>';
            }
        }

        // 物件一覧を表示
        function renderProperties(paginatedData) {
            const container = document.getElementById('properties-container');
            const countElement = document.getElementById('results-count');
            
            countElement.textContent = `検索結果: ${paginatedData.total}件`;

            if (paginatedData.data.length === 0) {
                container.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h2>該当する物件が見つかりませんでした</h2>
                        <p>検索条件を変更してお試しください</p>
                    </div>
                `;
                return;
            }

            const propertiesHTML = paginatedData.data.map(property => {
                const imageUrl = property.images && property.images.length > 0 
                    ? property.images[0].image_url 
                    : '';
                const price = property.price ? `${Number(property.price).toLocaleString()}万円` : '価格応談';
                const yield_rate = property.current_profit ? `${property.current_profit}%` : '-';
                const address = property.prefecture || property.city 
                    ? `${property.prefecture || ''}${property.city || ''}` 
                    : property.address || '住所非公開';

                return `
                    <div class="property-card" onclick="showPropertyDetail(${property.id})">
                        <span class="property-type">${property.property_type || '物件'}</span>
                        <div class="property-name">${property.property_name || '物件名未設定'}</div>
                        <div class="property-address">📍 ${address}</div>
                        <div class="property-details">
                            <div class="property-detail-item">
                                <span class="property-detail-label">利回り</span>
                                <span class="property-detail-value">${yield_rate}</span>
                            </div>
                            <div class="property-detail-item">
                                <span class="property-detail-label">取引形態</span>
                                <span class="property-detail-value">${property.transaction_category || '-'}</span>
                            </div>
                        </div>
                        <div class="property-price">${price}</div>
                    </div>
                `;
            }).join('');

            container.innerHTML = `<div class="properties-grid">${propertiesHTML}</div>`;
        }

        // ページネーション表示
        function renderPagination(paginatedData) {
            const container = document.getElementById('pagination-container');
            
            if (paginatedData.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <button class="pagination-btn" onclick="searchProperties(${paginatedData.current_page - 1})" 
                    ${paginatedData.current_page === 1 ? 'disabled' : ''}>
                    ← 前へ
                </button>
            `;

            const maxPages = 5;
            let startPage = Math.max(1, paginatedData.current_page - Math.floor(maxPages / 2));
            let endPage = Math.min(paginatedData.last_page, startPage + maxPages - 1);
            
            if (endPage - startPage < maxPages - 1) {
                startPage = Math.max(1, endPage - maxPages + 1);
            }

            if (startPage > 1) {
                paginationHTML += `<button class="pagination-btn" onclick="searchProperties(1)">1</button>`;
                if (startPage > 2) paginationHTML += `<span>...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button class="pagination-btn ${i === paginatedData.current_page ? 'active' : ''}" 
                        onclick="searchProperties(${i})">
                        ${i}
                    </button>
                `;
            }

            if (endPage < paginatedData.last_page) {
                if (endPage < paginatedData.last_page - 1) paginationHTML += `<span>...</span>`;
                paginationHTML += `<button class="pagination-btn" onclick="searchProperties(${paginatedData.last_page})">${paginatedData.last_page}</button>`;
            }

            paginationHTML += `
                <button class="pagination-btn" onclick="searchProperties(${paginatedData.current_page + 1})" 
                    ${paginatedData.current_page === paginatedData.last_page ? 'disabled' : ''}>
                    次へ →
                </button>
            `;

            container.innerHTML = paginationHTML;
        }

        // 物件詳細を表示
        async function showPropertyDetail(propertyId) {
            const modal = document.getElementById('property-modal');
            const modalBody = document.getElementById('modal-body');
            
            modalBody.innerHTML = '<div class="loading"><div class="spinner"></div><p>読み込み中...</p></div>';
            modal.classList.add('active');

            try {
                const response = await fetch(`/api/public/properties/${propertyId}`);
                const data = await response.json();

                if (data.success) {
                    renderPropertyDetail(data.data);
                }
            } catch (error) {
                console.error('Error fetching property detail:', error);
                modalBody.innerHTML = '<div style="padding: 2rem; text-align: center;">エラーが発生しました</div>';
            }
        }

        // 物件詳細をレンダリング
        function renderPropertyDetail(property) {
            const modalBody = document.getElementById('modal-body');
            const price = property.price ? `${Number(property.price).toLocaleString()}万円` : '価格応談';
            
            modalBody.innerHTML = `
                <div class="modal-body">
                    <span class="modal-property-type">${property.property_type || '物件'}</span>
                    <h2 class="modal-property-name">${property.property_name || '物件名未設定'}</h2>
                    <div class="modal-property-price">${price}</div>
                    
                    <div class="modal-detail-grid">
                        ${property.prefecture || property.city ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">所在地</div>
                                <div class="modal-detail-value">${property.prefecture || ''}${property.city || ''}</div>
                            </div>
                        ` : ''}
                        ${property.current_profit ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">現況利回り</div>
                                <div class="modal-detail-value">${property.current_profit}%</div>
                            </div>
                        ` : ''}
                        ${property.transaction_category ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">取引区分</div>
                                <div class="modal-detail-value">${property.transaction_category}</div>
                            </div>
                        ` : ''}
                        ${property.land_area ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">地積</div>
                                <div class="modal-detail-value">${property.land_area}㎡</div>
                            </div>
                        ` : ''}
                        ${property.building_area ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">建物面積</div>
                                <div class="modal-detail-value">${property.building_area}㎡</div>
                            </div>
                        ` : ''}
                        ${property.structure_floors ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">構造・階数</div>
                                <div class="modal-detail-value">${property.structure_floors}</div>
                            </div>
                        ` : ''}
                        ${property.construction_year ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">築年</div>
                                <div class="modal-detail-value">${property.construction_year}</div>
                            </div>
                        ` : ''}
                        ${property.nearest_station ? `
                            <div class="modal-detail-item">
                                <div class="modal-detail-label">最寄り駅</div>
                                <div class="modal-detail-value">${property.nearest_station}${property.walking_minutes ? ` 徒歩${property.walking_minutes}分` : ''}</div>
                            </div>
                        ` : ''}
                    </div>
                    
                    ${property.remarks ? `
                        <div class="modal-remarks">
                            <div class="modal-remarks-label">備考</div>
                            <div class="modal-remarks-text">${property.remarks}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        // モーダルを閉じる
        function closeModal(event) {
            if (!event || event.target.id === 'property-modal') {
                document.getElementById('property-modal').classList.remove('active');
            }
        }

        // 検索リセット
        function resetSearch() {
            document.getElementById('keyword').value = '';
            document.getElementById('prefecture').value = '';
            document.getElementById('city').value = '';
            document.getElementById('transaction_category').value = '';
            document.getElementById('price_min').value = '';
            document.getElementById('price_max').value = '';
            document.getElementById('yield_min').value = '';
            document.getElementById('building_age').value = '';
            document.querySelectorAll('input[name="property_type"]').forEach(cb => cb.checked = false);
            document.getElementById('sort-select').value = 'created_at-desc';
            searchProperties();
        }
    </script>
</body>
</html>

