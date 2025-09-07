<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hình ảnh</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <!-- Panzoom (zoom sâu, pan được) -->
    <!-- Panzoom (đúng phiên bản hỗ trợ trình duyệt) -->
    <script src="https://unpkg.com/@panzoom/panzoom@4.4.3/dist/panzoom.min.js"></script>

    <style>
        body {
            font-family: system-ui, sans-serif;
        }
        .zoom-container {
            overflow: hidden;
            border-radius: 0.5rem;
            cursor: grab;
        }
        .zoomable {
            display: block;
            max-width: 100%;
            height: auto;
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-100 py-10">
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">
        Thư viện <code class="text-blue-500">duyvu</code> (ảnh & video)
    </h1>

    <!-- Modal hiển thị ảnh full screen, căn giữa -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden items-center justify-center">
        <span class="absolute top-4 right-6 text-white text-4xl cursor-pointer z-50" id="closeModal">&times;</span>
        <div class="w-full h-full flex items-center justify-center overflow-hidden">
            <div class="max-w-full max-h-full overflow-auto">
                <img id="modalImage" src="" alt="" class="mx-auto my-auto max-h-screen object-contain cursor-grab" />
            </div>
        </div>
    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php
        $folder = 'duyvu/';
        $images = glob($folder . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $videos = glob($folder . '*.{mp4,webm}', GLOB_BRACE);
        $files = array_merge($images, $videos);

        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                echo '<div class="rounded overflow-hidden shadow-lg bg-white p-2">';

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    echo '
            <div class="zoom-container">
              <img src="' . $file . '" alt="' . $filename . '" class="zoomable rounded-md w-full h-auto">
            </div>
            <p class="mt-2 text-center text-sm text-gray-600">' . $filename . '</p>
          ';
                } elseif (in_array($ext, ['mp4', 'webm'])) {
                    echo '
            <video controls class="rounded-md w-full h-auto">
              <source src="' . $file . '" type="video/' . $ext . '">
              Trình duyệt không hỗ trợ video.
            </video>
            <p class="mt-2 text-center text-sm text-gray-600">' . $filename . '</p>
          ';
                }

                echo '</div>';
            }
        } else {
            echo '<p class="col-span-4 text-center text-gray-500">Không có ảnh hoặc video trong thư mục duyvu.</p>';
        }
        ?>
    </div>
</div>

<script>
    // GSAP hiệu ứng xuất hiện
    gsap.from('.zoomable, video', {
        opacity: 0,
        y: 30,
        duration: 0.6,
        stagger: 0.1,
        ease: "power2.out"
    });

    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeModal = document.getElementById('closeModal');
    let panzoomInstance = null;

    // Bắt sự kiện click vào ảnh để mở modal
    document.querySelectorAll('.zoomable').forEach(img => {
        img.addEventListener('click', () => {
            modalImage.src = img.src;
            modal.classList.remove('hidden');

            // Reset panzoom nếu có trước đó
            if (panzoomInstance) panzoomInstance.destroy();

            // Đợi ảnh load xong mới init panzoom
            modalImage.onload = () => {
                panzoomInstance = Panzoom(modalImage, {
                    maxScale: 5,
                    minScale: 1,
                    contain: 'outside',
                });

                modalImage.parentElement.addEventListener('wheel', panzoomInstance.zoomWithWheel);
            };
        });
    });

    // Đóng modal khi click nút X
    closeModal.addEventListener('click', () => {
        modal.classList.add('hidden');
        modalImage.src = "";
        if (panzoomInstance) panzoomInstance.destroy();
    });

    // Đóng modal khi nhấn Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modal.classList.add('hidden');
            modalImage.src = "";
            if (panzoomInstance) panzoomInstance.destroy();
        }
    });
</script></body>
</html>
