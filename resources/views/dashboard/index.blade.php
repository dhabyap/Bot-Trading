<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Bot Dashboard</title>
    <!-- Tailwind CSS (for quick layout) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js (for simple interactivity) -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .glow-green { box-shadow: 0 0 15px rgba(34, 197, 94, 0.2); }
        .glow-red { box-shadow: 0 0 15px rgba(239, 68, 68, 0.2); }
        
        .pulse {
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.1);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.5);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body x-data="dashboardApp()">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass-card px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-robot text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">Antigravity Bot</h1>
                <p class="text-xs text-slate-400">Monitoring Real-time</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div x-show="latency !== null" class="flex items-center gap-1 text-[10px] text-slate-500 font-mono">
                <i class="fas fa-signal" :class="latency < 200 ? 'text-green-500' : 'text-yellow-500'"></i>
                <span x-text="latency"></span>ms
            </div>
            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 text-xs border border-slate-700">
                <span class="w-2 h-2 rounded-full bg-green-500 pulse"></span>
                <span x-text="stats.bot_status.mode"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="fetchData(true)" class="p-2 hover:bg-slate-800 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt" :class="loading ? 'fa-spin' : ''"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6 space-y-8">
        
        <!-- Top Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            
            <!-- Price Card -->
            <div class="glass-card p-6 rounded-2xl glow-green">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-slate-400 text-sm font-medium">Estimated Balance</span>
                    <i class="fas fa-coins text-yellow-500"></i>
                </div>
                <div class="text-3xl font-bold tracking-tight text-white">
                    <span x-text="stats.total_usdt"></span>
                    <span class="text-lg font-normal text-slate-400">USDT</span>
                </div>
                <div class="mt-2 text-[10px] text-slate-500">
                    Calculated from USDT + BTC value
                </div>
            </div>

            <!-- Price Card -->

            <!-- Bot Status Card -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-slate-400 text-sm font-medium">Bot Status</span>
                    <i class="fas fa-power-off" :class="stats.bot_status.active ? 'text-green-500' : 'text-red-500'"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-bold tracking-tight" x-text="stats.bot_status.active ? 'ACTIVE' : 'STOPPED'"></span>
                        <span x-show="stats.bot_status.active" class="w-2 h-2 rounded-full bg-green-500 pulse"></span>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button @click="runBotManual()" :disabled="runningBot" 
                            class="flex-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-700 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-play" :class="runningBot ? 'fa-spin fa-spinner' : ''"></i>
                        RUN
                    </button>
                    <button @click="confirmKillSwitch()" :disabled="killing"
                            class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 disabled:bg-slate-700 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-red-900/20">
                        <i class="fas fa-skull-crossbones" :class="killing ? 'fa-spin' : ''"></i>
                        KILL
                    </button>
                </div>
                <div class="mt-3 text-[10px] text-slate-500">
                    Last run: <span x-text="stats.bot_status.last_run"></span>
                </div>
            </div>

            <!-- Active Strategy & Parameters -->
            <div class="glass-card p-6 rounded-2xl">
                <div class="flex justify-between items-start mb-3">
                    <span class="text-slate-400 text-sm font-medium">Active Strategy</span>
                    <i class="fas fa-microchip text-indigo-400"></i>
                </div>
                <div class="text-lg font-bold text-indigo-300 truncate" x-text="stats.strategy.name"></div>
                <div class="grid grid-cols-2 gap-2 mt-3">
                    <div class="bg-slate-800/50 p-2 rounded-lg border border-slate-700/50">
                        <div class="text-[9px] text-slate-500 uppercase font-bold">Target TP</div>
                        <div class="text-sm font-mono text-green-400" x-text="stats.strategy.tp + '%'"></div>
                    </div>
                    <div class="bg-slate-800/50 p-2 rounded-lg border border-slate-700/50">
                        <div class="text-[9px] text-slate-500 uppercase font-bold">Stop Loss</div>
                        <div class="text-sm font-mono text-red-400" x-text="stats.strategy.sl + '%'"></div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="glass-card p-6 rounded-2xl" :class="stats.performance.total_pnl >= 0 ? 'glow-green' : 'glow-red'">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-slate-400 text-sm font-medium">Performance Metrics</span>
                    <i class="fas fa-chart-bar text-slate-500"></i>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <div class="col-span-2">
                        <div class="text-[10px] text-slate-500 uppercase">Daily P&L</div>
                        <div class="text-2xl font-bold tracking-tight" :class="stats.performance.daily_pnl >= 0 ? 'text-green-500' : 'text-red-500'">
                            <span x-text="stats.performance.daily_pnl >= 0 ? '+' : ''"></span>
                            <span x-text="stats.performance.daily_pnl"></span><span class="text-xs ml-1">USDT</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[9px] text-slate-500 uppercase">Win Rate</div>
                        <div class="text-lg font-bold text-slate-200" x-text="stats.performance.win_rate + '%'"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[9px] text-slate-500 uppercase">Total P&L</div>
                        <div class="text-lg font-bold" :class="stats.performance.total_pnl >= 0 ? 'text-green-500' : 'text-red-500'">
                            <span x-text="stats.performance.total_pnl >= 0 ? '+' : ''"></span><span x-text="stats.performance.total_pnl"></span>
                        </div>
                    </div>
                    <div class="col-span-2 pt-1 border-t border-slate-700/50">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] text-slate-500 uppercase">Max Drawdown</span>
                            <span class="text-[11px] font-bold text-red-400" x-text="'-' + stats.performance.max_drawdown + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Open Position -->
            <div class="glass-card p-6 rounded-2xl">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-slate-400 text-sm font-medium">Open Position</span>
                    <i class="fas fa-wallet text-slate-500"></i>
                </div>
                <template x-if="stats.open_position">
                    <div>
                        <div class="text-xl font-bold tracking-tight text-indigo-400">
                            <span x-text="stats.open_position.amount"></span> <span x-text="stats.ticker.symbol.split('/')[0]"></span>
                        </div>
                        <div class="text-sm font-semibold mt-1" :class="stats.open_position.unrealized_pnl >= 0 ? 'text-green-500' : 'text-red-500'">
                            Unrealized: <span x-text="stats.open_position.unrealized_pnl >= 0 ? '+' : ''"></span><span x-text="stats.open_position.unrealized_pnl"></span> USDT
                        </div>
                    </div>
                </template>
                <template x-if="!stats.open_position">
                    <div class="text-lg font-medium text-slate-500">No active position</div>
                </template>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Recent Trades (Left 2 columns) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
                        <h2 class="font-bold text-lg"><i class="fas fa-history mr-2 text-indigo-500"></i>Trade History</h2>
                        <span class="text-xs text-slate-500">Last 50 records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-800/50 text-slate-400 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3">Time</th>
                                    <th class="px-6 py-3">Side</th>
                                    <th class="px-6 py-3">Symbol</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                    <th class="px-6 py-3 text-right">Price</th>
                                    <th class="px-6 py-3 text-right">P&L</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <template x-for="trade in trades" :key="trade.id">
                                    <tr class="hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 text-slate-400" x-text="formatDate(trade.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <span :class="trade.side === 'buy' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'" 
                                                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider" 
                                                  x-text="trade.side"></span>
                                        </td>
                                        <td class="px-6 py-4 font-medium" x-text="trade.symbol"></td>
                                        <td class="px-6 py-4 text-right font-mono" x-text="trade.amount"></td>
                                        <td class="px-6 py-4 text-right font-mono" x-text="trade.price"></td>
                                        <td class="px-6 py-4 text-right font-mono font-bold" :class="trade.profit_loss >= 0 ? 'text-green-500' : 'text-red-500'">
                                            <span x-show="trade.side === 'sell'" x-text="(trade.profit_loss >= 0 ? '+' : '') + trade.profit_loss"></span>
                                            <span x-show="trade.side === 'buy'" class="text-slate-600">-</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                
                <!-- Account Balance -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-bold mb-4 flex items-center"><i class="fas fa-coins mr-2 text-yellow-500"></i>Asset Balances</h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="(val, asset) in stats.balance" :key="asset">
                            <template x-if="val > 0">
                                <div class="flex justify-between items-center bg-slate-800/50 p-3 rounded-xl border border-slate-700/50">
                                    <span class="font-bold text-slate-300" x-text="asset"></span>
                                    <span class="font-mono text-slate-100" x-text="parseFloat(val).toFixed(6)"></span>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="glass-card rounded-2xl p-6 border-l-2 border-indigo-500">
                    <h3 class="font-bold mb-4 flex items-center"><i class="fas fa-clock mr-2 text-indigo-400"></i>Pending Orders</h3>
                    <div class="space-y-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                        <template x-if="stats.pending_orders.length === 0">
                            <div class="text-xs text-slate-500 text-center py-4">No pending orders</div>
                        </template>
                        <template x-for="order in stats.pending_orders" :key="order.id">
                            <div class="bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 relative overflow-hidden">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase" 
                                          :class="order.side === 'buy' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'" 
                                          x-text="order.side + ' ' + order.type"></span>
                                    <span class="text-[10px] text-slate-500" x-text="formatDateShort(order.datetime)"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-200" x-text="order.symbol"></span>
                                    <span class="text-xs font-mono text-indigo-400" x-text="order.amount + ' @ ' + order.price"></span>
                                </div>
                                <div class="mt-2 h-1 w-full bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500" :style="'width: ' + (order.filled / order.amount * 100) + '%'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Error Logs -->
                <div class="glass-card rounded-2xl p-6 border-t-2 border-red-500/30">
                    <h3 class="font-bold mb-4 flex items-center text-red-400"><i class="fas fa-exclamation-triangle mr-2"></i>Recent Errors</h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="err in errors" :key="err.id">
                            <div class="p-3 rounded-lg bg-red-500/5 border border-red-500/10 mb-3">
                                <div class="flex justify-between items-start text-[10px] mb-1">
                                    <span class="font-bold text-red-400 uppercase" x-text="err.source"></span>
                                    <span class="text-slate-500" x-text="formatDateShort(err.created_at)"></span>
                                </div>
                                <div class="text-xs text-slate-300 break-words" x-text="err.message"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function dashboardApp() {
            return {
                loading: false,
                runningBot: false,
                killing: false,
                checkingTelegram: false,
                telegramActive: false,
                latency: null,
                telegramMessage: 'Memeriksa koneksi...',
                stats: {
                    ticker: { symbol: 'BTC/USDT', last: 0, change: 0, high: 0, low: 0, volume: 0 },
                    balance: {},
                    total_usdt: 0,
                    performance: { daily_pnl: 0, total_pnl: 0, win_rate: 0, max_drawdown: 0 },
                    open_position: null,
                    pending_orders: [],
                    strategy: { name: '...', tp: 0, sl: 0 },
                    bot_status: { active: false, last_run: '...', mode: '...' }
                },
                trades: [],
                errors: [],
                
                init() {
                    this.fetchData();
                    this.checkTelegramStatus();
                    // Auto refresh every 30s
                    setInterval(() => this.fetchData(), 30000);
                },

                async fetchData(manual = false) {
                    const startTime = Date.now();
                    this.loading = true;
                    try {
                        const [statsRes, tradesRes, errorsRes] = await Promise.all([
                            fetch('/dashboard-api/stats'),
                            fetch('/dashboard-api/trades'),
                            fetch('/dashboard-api/errors')
                        ]);

                        const sData = await statsRes.json();
                        const tData = await tradesRes.json();
                        const eData = await errorsRes.json();

                        if (sData.success) {
                            this.stats = sData.data;
                            this.latency = sData.data.binance_latency;
                        }
                        if (tData.success) this.trades = tData.data;
                        if (eData.success) this.errors = eData.data;

                    } catch (err) {
                        console.error('Failed to fetch dashboard data:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                async runBotManual() {
                    if (this.runningBot) return;
                    this.runningBot = true;
                    try {
                        const res = await fetch('/dashboard-api/run-bot', { 
                            method: 'POST', 
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            } 
                        });
                        const data = await res.json();
                        alert(data.message + (data.output ? "\n\nOutput:\n" + data.output : ""));
                        await this.fetchData();
                    } catch (err) {
                        alert('Gagal menjalankan bot secara manual');
                    } finally {
                        this.runningBot = false;
                    }
                },

                confirmKillSwitch() {
                    if (confirm('⚠️ PERINGATAN: Tombol ini akan menghentikan bot, membatalkan semua order, dan menjual seluruh posisi aktif ke USDT. Anda yakin?')) {
                        this.killSwitch();
                    }
                },

                async killSwitch() {
                    this.killing = true;
                    try {
                        const res = await fetch('/dashboard-api/kill-switch', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        alert(data.message);
                        await this.fetchData();
                    } catch (err) {
                        alert('Gagal mengeksekusi Kill Switch');
                    } finally {
                        this.killing = false;
                    }
                },

                async checkTelegramStatus() {
                    this.checkingTelegram = true;
                    try {
                        const res = await fetch('/dashboard-api/check-telegram');
                        const data = await res.json();
                        this.telegramActive = data.active;
                        this.telegramMessage = data.message;
                    } catch (err) {
                        this.telegramActive = false;
                        this.telegramMessage = 'Gagal menghubungi server';
                    } finally {
                        this.checkingTelegram = false;
                    }
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
                },

                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' ' + 
                           date.toLocaleDateString([], { day: '2-digit', month: 'short' });
                },

                formatDateShort(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        }
    </script>
</body>
</html>
