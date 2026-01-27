import { ref, reactive } from 'vue'

export function useDraggable(initialPos = { top: 200, left: 300 }) {
    const position = reactive(initialPos)
    const headerRef = ref(null)

    let isDragging = false
    let startX = 0
    let startY = 0
    let startLeft = 0
    let startTop = 0

    const onMouseDown = (e) => {
        isDragging = true
        startX = e.clientX
        startY = e.clientY
        startLeft = position.left
        startTop = position.top

        document.addEventListener('mousemove', onMouseMove)
        document.addEventListener('mouseup', onMouseUp)
    }

    const onMouseMove = (e) => {
        if (!isDragging) return
        const dx = e.clientX - startX
        const dy = e.clientY - startY
        position.left = startLeft + dx
        position.top = startTop + dy
    }

    const onMouseUp = () => {
        isDragging = false
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
    }

    return {
        position,
        onMouseDown,
        headerRef
    }
}
