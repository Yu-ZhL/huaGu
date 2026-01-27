import { createApp } from 'vue'
import App from '../App.vue'
import '../style.css'

// 创建容器
const root = document.createElement('div')
root.id = 'feimao-extension-root'
document.body.appendChild(root)

// 使用 Shadow DOM 隔离样式 (可选，但推荐)
// const shadow = root.attachShadow({ mode: 'open' })
// const appRoot = document.createElement('div')
// shadow.appendChild(appRoot) 
// 注意：如果使用 Shadow DOM，样式注入需要特殊处理 (style-loader等)，
// 这里为了简化和确保 Tailwind/CSS 工作，先直接挂载在 root 上，但在 App.vue 中从严控制样式作用域

createApp(App).mount(root)
