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
                        <th scope="col" class="px-6 py-3 w-32">缓存类型</th>
                        <th scope="col" class="px-6 py-3">键名 (Key)</th>
                        <th scope="col" class="px-6 py-3 w-40">有效期 (TTL)</th>
                        <th scope="col" class="px-6 py-3 w-40 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->cacheItems as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" x-data="{ 
                                                                                ttl: {{ $item['ttl'] }},
                                                                                showContent: false,
                                                                                url: '{{ $item['url'] }}',
                                                                                payload: @js($item['payload']),
                                                                                response: @js($item['response']),
                                                                                rawContent: @js($item['content'])
                                                                            }" x-init="
                                                                                    if(ttl > 0) {
                                                                                        setInterval(() => { if(ttl > 0) ttl--; }, 1000)
                                                                                    }
                                                                            ">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <x-filament::badge :color="$item['type'] === '身份令牌' ? 'warning' : 'success'">
                                    {{ $item['type'] }}
                                </x-filament::badge>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs max-w-[300px] truncate" title="{{ $item['key'] }}">
                                {{ $item['short_key'] }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold" :class="{'text-danger-600': ttl < 60 && ttl > 0}" x-text="
                                                                                        ttl === -1 ? '永久有效' : 
                                                                                        (ttl <= -2 ? '已过期' : 
                                                                                        ((Math.floor(ttl/3600) > 0 ? Math.floor(ttl/3600) + 'h ' : '') + 
                                                                                        (Math.floor((ttl%3600)/60) > 0 ? Math.floor((ttl%3600)/60) + 'm ' : '') + 
                                                                                        (ttl%60) + 's'))
                                                                                    "></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-2">
                                    <x-filament::icon-button icon="heroicon-o-eye" color="info" tooltip="查看详情"
                                        @click="showContent = true" size="sm" />
                                    <x-filament::icon-button icon="heroicon-o-arrow-path" color="gray" tooltip="重新请求"
                                        wire:click="refreshItem('{{ $item['key'] }}')" size="sm" />
                                    <x-filament::icon-button icon="heroicon-o-trash" color="danger" tooltip="删除"
                                        wire:click="deleteItem('{{ $item['key'] }}')" wire:confirm="确定要删除此缓存吗？" size="sm" />
                                </div>

                                {{-- 查看详情的弹窗 --}}
                                <template x-if="showContent">
                                    <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 bg-black/50 overflow-y-auto"
                                        @click.self="showContent = false">
                                        <div
                                            class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col text-left">
                                            <div
                                                class="px-6 py-4 border-b dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50 rounded-t-lg">
                                                <h3 class="text-lg font-bold">缓存详情 - <span
                                                        class="text-gray-500 text-base font-normal">{{ $item['key'] }}</span>
                                                </h3>
                                                <button @click="showContent = false"
                                                    class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                                            </div>
                                            <div
                                                class="p-6 overflow-y-auto flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                <!-- 左侧栏：请求详情 -->
                                                <div class="space-y-4">
                                                    <div>
                                                        <h4 class="font-bold text-sm text-gray-500 uppercase mb-2">请求地址</h4>
                                                        <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded font-mono text-xs break-all border border-gray-200 dark:border-gray-700"
                                                            x-text="url"></div>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-sm text-gray-500 uppercase mb-2">请求参数
                                                            (Payload)</h4>
                                                        <template x-if="payload">
                                                            <pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded text-xs overflow-x-auto border border-gray-200 dark:border-gray-700"
                                                                x-text="JSON.stringify(payload, null, 2)"></pre>
                                                        </template>
                                                        <template x-if="!payload">
                                                            <div class="text-gray-400 italic text-sm">无请求参数记录</div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- 右侧栏：响应数据 -->
                                                <div>
                                                    <h4 class="font-bold text-sm text-gray-500 uppercase mb-2">响应数据
                                                        (Response)</h4>
                                                    <template x-if="response">
                                                        <pre class="bg-green-50 dark:bg-green-900/20 p-3 rounded text-xs overflow-x-auto border border-green-100 dark:border-green-900/30"
                                                            x-text="JSON.stringify(response, null, 2)"></pre>
                                                    </template>
                                                    <template x-if="!response">
                                                        <div class="space-y-2">
                                                            <div class="text-gray-400 italic text-sm mb-2">未检测到结构化响应，显示原始内容：
                                                            </div>
                                                            <pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded text-xs overflow-x-auto h-[400px]"
                                                                x-text="rawContent"></pre>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div
                                                class="px-6 py-4 border-t dark:border-gray-800 text-right bg-gray-50 dark:bg-gray-800/50 rounded-b-lg">
                                                <x-filament::button color="gray"
                                                    @click="showContent = false">关闭</x-filament::button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <x-heroicon-o-inbox class="w-8 h-8 text-gray-300" />
                                    <span>暂无缓存数据</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>