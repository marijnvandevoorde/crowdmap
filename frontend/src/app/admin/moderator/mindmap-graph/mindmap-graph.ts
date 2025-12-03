import {
  Component,
  ElementRef,
  Input,
  Output,
  EventEmitter,
  OnChanges,
  SimpleChanges,
  AfterViewInit,
  OnDestroy,
  ViewChild,
} from '@angular/core';
import * as d3 from 'd3';
import { Statement } from '../../../core/mindmap';

interface TreeNode {
  id: number;
  parentId: number | null;
  content: string;
  voteCount: number;
  children?: TreeNode[];
  wrappedLines?: string[];
  nodeHeight?: number;
}

interface D3Node extends d3.HierarchyPointNode<TreeNode> {
  x0?: number;
  y0?: number;
}

@Component({
  selector: 'app-mindmap-graph',
  standalone: true,
  template: `<div #container class="mindmap-container"></div>`,
  styles: [`
    .mindmap-container {
      width: 100%;
      height: 100%;
      overflow: hidden;
    }
  `],
})
export class MindmapGraphComponent implements AfterViewInit, OnChanges, OnDestroy {
  @ViewChild('container', { static: true }) containerRef!: ElementRef<HTMLDivElement>;

  @Input() statements: Statement[] = [];
  @Input() focusedStatementId: number | null = null;
  @Input() selectedNodeIds: Set<number> = new Set();
  @Output() nodeClick = new EventEmitter<number>();
  @Output() nodeSelect = new EventEmitter<number>();

  private svg!: d3.Selection<SVGSVGElement, unknown, null, undefined>;
  private g!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private linksGroup!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private nodesGroup!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private zoom!: d3.ZoomBehavior<SVGSVGElement, unknown>;
  private width = 800;
  private height = 600;
  private nodeWidth = 200;
  private nodeMinHeight = 50;
  private lineHeight = 16;
  private nodePadding = 20;
  private initialized = false;

  ngAfterViewInit(): void {
    this.initializeSvg();
    this.initialized = true;
    // Delay initial render to ensure container has dimensions
    setTimeout(() => {
      this.render();
    }, 0);
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (this.initialized) {
      this.render();
    }
  }

  ngOnDestroy(): void {
    // Cleanup if needed
  }

  private initializeSvg(): void {
    const container = this.containerRef.nativeElement;
    this.width = container.clientWidth || 800;
    this.height = container.clientHeight || 600;

    // Create SVG
    this.svg = d3.select(container)
      .append('svg')
      .attr('width', '100%')
      .attr('height', '100%')
      .attr('viewBox', `0 0 ${this.width} ${this.height}`)
      .style('background', 'transparent');

    // Add defs for gradients and filters
    const defs = this.svg.append('defs');

    // Glow filter for focused nodes
    const filter = defs.append('filter')
      .attr('id', 'glow')
      .attr('x', '-50%')
      .attr('y', '-50%')
      .attr('width', '200%')
      .attr('height', '200%');

    filter.append('feGaussianBlur')
      .attr('stdDeviation', '4')
      .attr('result', 'coloredBlur');

    const feMerge = filter.append('feMerge');
    feMerge.append('feMergeNode').attr('in', 'coloredBlur');
    feMerge.append('feMergeNode').attr('in', 'SourceGraphic');

    // Gradient for root node
    const rootGradient = defs.append('linearGradient')
      .attr('id', 'rootGradient')
      .attr('x1', '0%')
      .attr('y1', '0%')
      .attr('x2', '100%')
      .attr('y2', '100%');

    rootGradient.append('stop')
      .attr('offset', '0%')
      .attr('stop-color', '#4fc3f7');

    rootGradient.append('stop')
      .attr('offset', '100%')
      .attr('stop-color', '#29b6f6');

    // Gradient for focused node
    const focusGradient = defs.append('linearGradient')
      .attr('id', 'focusGradient')
      .attr('x1', '0%')
      .attr('y1', '0%')
      .attr('x2', '100%')
      .attr('y2', '100%');

    focusGradient.append('stop')
      .attr('offset', '0%')
      .attr('stop-color', '#1e3a5f');

    focusGradient.append('stop')
      .attr('offset', '100%')
      .attr('stop-color', '#16213e');

    // Create main group for zoom/pan
    this.g = this.svg.append('g');

    // Create separate groups for links and nodes (links first so they're behind)
    this.linksGroup = this.g.append('g').attr('class', 'links-group');
    this.nodesGroup = this.g.append('g').attr('class', 'nodes-group');

    // Setup zoom behavior
    this.zoom = d3.zoom<SVGSVGElement, unknown>()
      .scaleExtent([0.3, 2])
      .on('zoom', (event) => {
        this.g.attr('transform', event.transform);
      });

    this.svg.call(this.zoom);
  }

  private buildTree(): TreeNode | null {
    if (this.statements.length === 0) return null;

    const root = this.statements.find(s => s.parentId === null);
    if (!root) return null;

    const buildNode = (statement: Statement): TreeNode => {
      const children = this.statements.filter(s => s.parentId === statement.id);
      const isRoot = statement.parentId === null;
      const wrappedLines = this.wrapText(statement.content, isRoot ? 24 : 28);
      const hasVotes = statement.parentId !== null;
      const textHeight = wrappedLines.length * this.lineHeight;
      const voteHeight = hasVotes ? 20 : 0;
      const nodeHeight = Math.max(this.nodeMinHeight, textHeight + voteHeight + this.nodePadding);

      return {
        id: statement.id,
        parentId: statement.parentId,
        content: statement.content,
        voteCount: statement.voteCount,
        wrappedLines,
        nodeHeight,
        children: children.length > 0 ? children.map(buildNode) : undefined,
      };
    };

    return buildNode(root);
  }

  private getFocusPath(): Set<number> {
    if (!this.focusedStatementId) return new Set();

    const path = new Set<number>();
    let current = this.statements.find(s => s.id === this.focusedStatementId);

    while (current) {
      path.add(current.id);
      current = current.parentId
        ? this.statements.find(s => s.id === current!.parentId)
        : undefined;
    }

    return path;
  }

  private render(): void {
    const treeData = this.buildTree();
    if (!treeData) return;

    const focusPath = this.getFocusPath();

    // Create hierarchy
    const root = d3.hierarchy(treeData);

    // Find the maximum node height for proper spacing
    const maxNodeHeight = Math.max(...root.descendants().map(d => d.data.nodeHeight || this.nodeMinHeight));

    // Calculate tree layout - horizontal tree
    const treeLayout = d3.tree<TreeNode>()
      .nodeSize([maxNodeHeight + 30, this.nodeWidth + 80]);

    const treeRoot = treeLayout(root) as D3Node;

    // Get all nodes and links
    const nodes = treeRoot.descendants() as D3Node[];
    const links = treeRoot.links();

    // Center the tree
    let minY = Infinity, maxY = -Infinity;
    let minX = Infinity, maxX = -Infinity;
    nodes.forEach(d => {
      if (d.y < minY) minY = d.y;
      if (d.y > maxY) maxY = d.y;
      if (d.x < minX) minX = d.x;
      if (d.x > maxX) maxX = d.x;
    });

    const treeWidth = maxY - minY + this.nodeWidth;
    const treeHeight = maxX - minX + maxNodeHeight;

    const offsetX = (this.width - treeWidth) / 2 - minY + this.nodeWidth / 2;
    const offsetY = (this.height - treeHeight) / 2 - minX + maxNodeHeight / 2;

    // Custom curved link generator
    const linkGenerator = d3.linkHorizontal<d3.HierarchyPointLink<TreeNode>, d3.HierarchyPointNode<TreeNode>>()
      .x(d => d.y + offsetX)
      .y(d => d.x + offsetY);

    // Update links (in linksGroup so they're always behind nodes)
    const linkSelection = this.linksGroup.selectAll<SVGPathElement, d3.HierarchyPointLink<TreeNode>>('.link')
      .data(links, d => `${d.source.data.id}-${d.target.data.id}`);

    // Enter new links
    const linkEnter = linkSelection.enter()
      .append('path')
      .attr('class', 'link')
      .attr('fill', 'none')
      .attr('stroke-width', 2)
      .attr('d', linkGenerator)
      .attr('stroke', d => focusPath.has(d.target.data.id) ? '#4fc3f7' : 'rgba(255, 255, 255, 0.3)');

    // Merge enter and update, then apply transitions
    const linkUpdate = linkEnter.merge(linkSelection);

    linkUpdate.transition()
      .duration(500)
      .attr('d', linkGenerator)
      .attr('stroke', d => focusPath.has(d.target.data.id) ? '#4fc3f7' : 'rgba(255, 255, 255, 0.3)')
      .attr('stroke-width', d => focusPath.has(d.target.data.id) ? 3 : 2);

    // Remove old links
    linkSelection.exit()
      .transition()
      .duration(300)
      .attr('opacity', 0)
      .remove();

    // Update nodes (in nodesGroup so they're always in front of links)
    const nodeSelection = this.nodesGroup.selectAll<SVGGElement, D3Node>('.node')
      .data(nodes, d => d.data.id.toString());

    // Enter new nodes
    const nodeEnter = nodeSelection.enter()
      .append('g')
      .attr('class', 'node')
      .attr('transform', d => `translate(${d.y + offsetX},${d.x + offsetY})`)
      .style('cursor', 'pointer')
      .on('click', (event, d) => {
        event.stopPropagation();
        if (event.metaKey || event.ctrlKey) {
          this.nodeSelect.emit(d.data.id);
        }
      })
      .on('dblclick', (event, d) => {
        event.stopPropagation();
        this.nodeClick.emit(d.data.id);
      });

    // Node background rect
    nodeEnter.append('rect')
      .attr('class', 'node-bg')
      .attr('x', -this.nodeWidth / 2)
      .attr('y', d => -(d.data.nodeHeight || this.nodeMinHeight) / 2)
      .attr('width', this.nodeWidth)
      .attr('height', d => d.data.nodeHeight || this.nodeMinHeight)
      .attr('rx', 12)
      .attr('ry', 12)
      .attr('fill', d => d.data.parentId === null ? '#4fc3f7' : '#16213e')
      .attr('stroke', d => d.data.parentId === null ? '#29b6f6' : 'rgba(255,255,255,0.1)')
      .attr('stroke-width', 2);

    // Node text - multi-line support
    const textGroups = nodeEnter.append('g')
      .attr('class', 'node-text-group');

    textGroups.each((d, i, nodes) => {
      const group = d3.select(nodes[i]);
      const lines = d.data.wrappedLines || [d.data.content];
      const isRoot = d.data.parentId === null;
      const hasVotes = !isRoot;
      const fontSize = isRoot ? 14 : 12;
      const totalTextHeight = lines.length * this.lineHeight;
      const voteHeight = hasVotes ? 20 : 0;
      const startY = -totalTextHeight / 2 - voteHeight / 2 + fontSize / 2;

      lines.forEach((line, lineIndex) => {
        group.append('text')
          .attr('class', 'node-text')
          .attr('text-anchor', 'middle')
          .attr('y', startY + lineIndex * this.lineHeight)
          .attr('fill', isRoot ? '#1a1a2e' : 'white')
          .attr('font-size', `${fontSize}px`)
          .attr('font-weight', isRoot ? '600' : '400')
          .text(line);
      });

      // Vote count for non-root nodes
      if (hasVotes) {
        group.append('text')
          .attr('class', 'vote-text')
          .attr('text-anchor', 'middle')
          .attr('y', startY + lines.length * this.lineHeight + 4)
          .attr('fill', 'rgba(255, 255, 255, 0.5)')
          .attr('font-size', '10px')
          .text(`▲ ${d.data.voteCount} votes`);
      }
    });

    // Focus indicator
    nodeEnter.append('text')
      .attr('class', 'focus-indicator')
      .attr('text-anchor', 'middle')
      .attr('y', d => -(d.data.nodeHeight || this.nodeMinHeight) / 2 - 8)
      .attr('fill', '#4fc3f7')
      .attr('font-size', '10px')
      .attr('font-weight', '700')
      .attr('opacity', d => d.data.id === this.focusedStatementId ? 1 : 0)
      .text('● ACTIVE');

    // Update all nodes (enter + update)
    const nodeUpdate = nodeEnter.merge(nodeSelection);

    nodeUpdate.transition()
      .duration(500)
      .attr('transform', d => `translate(${d.y + offsetX},${d.x + offsetY})`);

    // Update node backgrounds
    nodeUpdate.select('.node-bg')
      .transition()
      .duration(300)
      .attr('y', d => -(d.data.nodeHeight || this.nodeMinHeight) / 2)
      .attr('height', d => d.data.nodeHeight || this.nodeMinHeight)
      .attr('fill', d => {
        if (d.data.parentId === null) return '#4fc3f7';
        if (this.selectedNodeIds.has(d.data.id)) return '#2e5a3f';
        if (d.data.id === this.focusedStatementId) return '#1e3a5f';
        if (focusPath.has(d.data.id)) return '#1e2a4a';
        return '#16213e';
      })
      .attr('stroke', d => {
        if (d.data.parentId === null) return '#29b6f6';
        if (this.selectedNodeIds.has(d.data.id)) return '#4caf50';
        if (d.data.id === this.focusedStatementId) return '#4fc3f7';
        if (focusPath.has(d.data.id)) return 'rgba(79, 195, 247, 0.5)';
        return 'rgba(255,255,255,0.1)';
      })
      .attr('stroke-width', d => this.selectedNodeIds.has(d.data.id) || d.data.id === this.focusedStatementId ? 3 : 2);

    // Update text groups - rebuild if content changed
    nodeUpdate.each((d, i, nodes) => {
      const node = d3.select(nodes[i]);
      const textGroup = node.select('.node-text-group');

      // Clear and rebuild text group
      textGroup.selectAll('*').remove();

      const lines = d.data.wrappedLines || [d.data.content];
      const isRoot = d.data.parentId === null;
      const hasVotes = !isRoot;
      const fontSize = isRoot ? 14 : 12;
      const totalTextHeight = lines.length * this.lineHeight;
      const voteHeight = hasVotes ? 20 : 0;
      const startY = -totalTextHeight / 2 - voteHeight / 2 + fontSize / 2;

      lines.forEach((line, lineIndex) => {
        textGroup.append('text')
          .attr('class', 'node-text')
          .attr('text-anchor', 'middle')
          .attr('y', startY + lineIndex * this.lineHeight)
          .attr('fill', isRoot ? '#1a1a2e' : 'white')
          .attr('font-size', `${fontSize}px`)
          .attr('font-weight', isRoot ? '600' : '400')
          .text(line);
      });

      if (hasVotes) {
        textGroup.append('text')
          .attr('class', 'vote-text')
          .attr('text-anchor', 'middle')
          .attr('y', startY + lines.length * this.lineHeight + 4)
          .attr('fill', 'rgba(255, 255, 255, 0.5)')
          .attr('font-size', '10px')
          .text(`▲ ${d.data.voteCount} votes`);
      }
    });

    // Update focus indicator
    nodeUpdate.select('.focus-indicator')
      .transition()
      .duration(300)
      .attr('y', d => -(d.data.nodeHeight || this.nodeMinHeight) / 2 - 8)
      .attr('opacity', d => d.data.id === this.focusedStatementId ? 1 : 0);

    // Remove old nodes
    nodeSelection.exit()
      .transition()
      .duration(300)
      .attr('opacity', 0)
      .remove();

    // Add hover effects
    nodeUpdate
      .on('mouseenter', function(event, d) {
        d3.select(this).select('.node-bg')
          .transition()
          .duration(150)
          .attr('transform', 'scale(1.05)');
      })
      .on('mouseleave', function(event, d) {
        d3.select(this).select('.node-bg')
          .transition()
          .duration(150)
          .attr('transform', 'scale(1)');
      });
  }

  private wrapText(text: string, maxCharsPerLine: number): string[] {
    const words = text.split(/\s+/);
    const lines: string[] = [];
    let currentLine = '';

    for (const word of words) {
      if (!currentLine) {
        currentLine = word;
      } else if ((currentLine + ' ' + word).length <= maxCharsPerLine) {
        currentLine += ' ' + word;
      } else {
        lines.push(currentLine);
        currentLine = word;
      }
    }

    if (currentLine) {
      lines.push(currentLine);
    }

    return lines.length > 0 ? lines : [''];
  }

  // Public method to reset zoom
  resetZoom(): void {
    this.svg.transition()
      .duration(500)
      .call(this.zoom.transform, d3.zoomIdentity);
  }
}
