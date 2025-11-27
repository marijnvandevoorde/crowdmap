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
  @Output() nodeClick = new EventEmitter<number>();

  private svg!: d3.Selection<SVGSVGElement, unknown, null, undefined>;
  private g!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private linksGroup!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private nodesGroup!: d3.Selection<SVGGElement, unknown, null, undefined>;
  private zoom!: d3.ZoomBehavior<SVGSVGElement, unknown>;
  private width = 800;
  private height = 600;
  private nodeWidth = 200;
  private nodeHeight = 60;
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
      return {
        id: statement.id,
        parentId: statement.parentId,
        content: statement.content,
        voteCount: statement.voteCount,
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

    // Calculate tree layout - horizontal tree
    const treeLayout = d3.tree<TreeNode>()
      .nodeSize([this.nodeHeight + 30, this.nodeWidth + 80]);

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
    const treeHeight = maxX - minX + this.nodeHeight;

    const offsetX = (this.width - treeWidth) / 2 - minY + this.nodeWidth / 2;
    const offsetY = (this.height - treeHeight) / 2 - minX + this.nodeHeight / 2;

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
        this.nodeClick.emit(d.data.id);
      });

    // Node background rect
    nodeEnter.append('rect')
      .attr('class', 'node-bg')
      .attr('x', -this.nodeWidth / 2)
      .attr('y', -this.nodeHeight / 2)
      .attr('width', this.nodeWidth)
      .attr('height', this.nodeHeight)
      .attr('rx', 12)
      .attr('ry', 12)
      .attr('fill', d => d.data.parentId === null ? '#4fc3f7' : '#16213e')
      .attr('stroke', d => d.data.parentId === null ? '#29b6f6' : 'rgba(255,255,255,0.1)')
      .attr('stroke-width', 2);

    // Node text
    nodeEnter.append('text')
      .attr('class', 'node-text')
      .attr('text-anchor', 'middle')
      .attr('dy', d => d.data.parentId === null ? '0' : '-0.3em')
      .attr('fill', 'white')
      .attr('font-size', d => d.data.parentId === null ? '14px' : '12px')
      .attr('font-weight', d => d.data.parentId === null ? '600' : '400')
      .text(d => this.truncateText(d.data.content, 25));

    // Vote count for non-root nodes
    nodeEnter.filter(d => d.data.parentId !== null)
      .append('text')
      .attr('class', 'vote-text')
      .attr('text-anchor', 'middle')
      .attr('dy', '1.2em')
      .attr('fill', 'rgba(255, 255, 255, 0.5)')
      .attr('font-size', '10px')
      .text(d => `▲ ${d.data.voteCount} votes`);

    // Focus indicator
    nodeEnter.append('text')
      .attr('class', 'focus-indicator')
      .attr('text-anchor', 'middle')
      .attr('y', -this.nodeHeight / 2 - 8)
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
      .attr('fill', d => {
        if (d.data.parentId === null) return '#4fc3f7';
        if (d.data.id === this.focusedStatementId) return '#1e3a5f';
        if (focusPath.has(d.data.id)) return '#1e2a4a';
        return '#16213e';
      })
      .attr('stroke', d => {
        if (d.data.parentId === null) return '#29b6f6';
        if (d.data.id === this.focusedStatementId) return '#4fc3f7';
        if (focusPath.has(d.data.id)) return 'rgba(79, 195, 247, 0.5)';
        return 'rgba(255,255,255,0.1)';
      })
      .attr('stroke-width', d => d.data.id === this.focusedStatementId ? 3 : 2);

    // Update node text
    nodeUpdate.select('.node-text')
      .attr('fill', d => d.data.parentId === null ? '#1a1a2e' : 'white')
      .text(d => this.truncateText(d.data.content, 25));

    // Update vote counts with animation for changes
    nodeUpdate.select('.vote-text')
      .text(d => `▲ ${d.data.voteCount} votes`);

    // Update focus indicator
    nodeUpdate.select('.focus-indicator')
      .transition()
      .duration(300)
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

  private truncateText(text: string, maxLength: number): string {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
  }

  // Public method to reset zoom
  resetZoom(): void {
    this.svg.transition()
      .duration(500)
      .call(this.zoom.transform, d3.zoomIdentity);
  }
}
