import { ref, reactive, onMounted, onUnmounted } from 'vue'

export function useDraggable(initialPos = { top: 200, left: 300 }) {
    const position = reactive(initialPos)
    const headerRef = ref(null)

    let isDragging = false
    let startX = 0
    let startY = 0
    let startLeft = 0
    let startTop = 0

    // 鼠标按下，开始拖拽
    const onMouseDown = (e) => {
        isDragging = true
        // 记录初始点击位置
        startX = e.clientX
        startY = e.clientY
        // 记录元素当前位置
        startLeft = position.left
        startTop = position.top

        // 绑定移动和松开事件到 document，防止鼠标移出元素导致丢失
        document.addEventListener('mousemove', onMouseMove)
        document.addEventListener('mouseup', onMouseUp)
    }

    // 鼠标移动，更新位置
    const onMouseMove = (e) => {
        if (!isDragging) return

        // 计算偏移量
        const dx = e.clientX - startX
        const dy = e.clientY - startY

        // 更新位置
        position.left = startLeft + dx
        position.top = startTop + dy
    }

    // 鼠标松开，结束拖拽
    const onMouseUp = () => {
        isDragging = false
        // 移除全局事件监听
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
    }



    const adjustPosition = () => {
        const { innerWidth, innerHeight } = window
        const cardWidth = 320 // 预估宽度
        const cardHeight = 600 // 预估最大高度

        if (position.left + cardWidth > innerWidth) {
            position.left = Math.max(0, innerWidth - cardWidth - 20)
        }
        if (position.top + cardHeight > innerHeight) {
            position.top = Math.max(0, innerHeight - cardHeight - 20)
        }
    }

    onMounted(() => {
        window.addEventListener('resize', adjustPosition)
        // 初始也检查一次
        adjustPosition()
    })

    onUnmounted(() => {
        window.removeEventListener('resize', adjustPosition)
    })

    return {
        position,
        onMouseDown,
        headerRef
    }
}
