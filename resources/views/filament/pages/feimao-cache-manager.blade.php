<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Redis 缓存列表</h2>
            <x-filament::button wire:click="$refresh">
                刷新列表
            </x-filament::button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">缓存类型</th>
                        <th scope="col" class="px-6 py-3">键名 (Key)</th>
                        <th scope="col" class="px-6 py-3">有效期 (TTL)</th>
                        <th scope="col" class="px-6 py-3 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->cacheItems as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                            x-data="{ ttl: {{ $item['ttl'] }} }" x-init="
                                    if(ttl > 0) {
                                        setInterval(() => { if(ttl > 0) ttl--; }, 1000)
                                    }
                                ">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <x-filament::badge :color="$item['type'] === '身份令牌' ? 'warning' : 'success'">
                                    {{ $item['type'] }}
                                </x-filament::badge>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $item['key'] }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold" :class="{'text-danger-600': ttl < 60 && ttl > 0}" x-text="
                                        ttl === -1 ? '永久有效' : 
                                        (ttl === -2 ? '已过期' : 
                                        (Math.floor(ttl/3600) > 0 ? Math.floor(ttl/3600) + '小时 ' : '') + 
                                        (Math.floor((ttl%3600)/60) > 0 ? Math.floor((ttl%3600)/60) + '分 ' : '') + 
                                        (ttl%60) + '秒')
                                    "></span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <x-filament::button size="xs" color="gray" wire:click="refreshItem('{{ $item['key'] }}')">
                                    刷新
                                </x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="deleteItem('{{ $item['key'] }}')"
                                    wire:confirm="确定要删除此缓存吗？">
                                    删除
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center">暂无缓存数据</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>