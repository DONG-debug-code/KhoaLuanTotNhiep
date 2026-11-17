import { Fragment } from "react";

const INLINE_PATTERN = /(\*\*[^*]+\*\*|\[[^\]]+\]\(https?:\/\/[^\s)]+\)|https?:\/\/[^\s)]+)/g;
const MARKDOWN_LINK = /^\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)$/;

function renderInline(text) {
  return text.split(INLINE_PATTERN).map((part, index) => {
    if (part.startsWith("**") && part.endsWith("**") && part.length > 4) {
      return <strong key={index}>{part.slice(2, -2)}</strong>;
    }
    const markdownLink = part.match(MARKDOWN_LINK);
    if (markdownLink) {
      return (
        <a key={index} href={markdownLink[2]} target="_blank" rel="noopener noreferrer" className="text-[#AC0021] underline">
          {markdownLink[1]}
        </a>
      );
    }
    if (/^https?:\/\//.test(part)) {
      return (
        <a key={index} href={part} target="_blank" rel="noopener noreferrer" className="text-[#AC0021] underline">
          {part}
        </a>
      );
    }
    return <Fragment key={index}>{part.replace(/\*/g, "")}</Fragment>;
  });
}

// Hiển thị markdown tối giản (in đậm, gạch đầu dòng, link) bằng React element, không dùng innerHTML
export default function ChatMarkdown({ text }) {
  const blocks = [];
  let list = [];

  const flushList = () => {
    if (list.length) {
      blocks.push({ type: "list", items: list });
      list = [];
    }
  };

  String(text ?? "").split("\n").forEach((rawLine) => {
    const line = rawLine.trimEnd();
    const bullet = line.match(/^\s*(?:[-*•]|\d+\.)\s+(.*)$/);
    if (bullet) {
      list.push(bullet[1]);
      return;
    }
    flushList();
    if (line.trim()) blocks.push({ type: "text", value: line.replace(/^#{1,6}\s*/, "") });
  });
  flushList();

  return (
    <div className="space-y-2">
      {blocks.map((block, index) => (
        block.type === "list" ? (
          <ul key={index} className="list-disc space-y-1 pl-5">
            {block.items.map((item, i) => <li key={i}>{renderInline(item)}</li>)}
          </ul>
        ) : (
          <p key={index}>{renderInline(block.value)}</p>
        )
      ))}
    </div>
  );
}
